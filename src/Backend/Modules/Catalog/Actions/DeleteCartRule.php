<?php

namespace Backend\Modules\Catalog\Actions;

use Backend\Core\Engine\Base\ActionDelete as BackendBaseActionDelete;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Language\Locale;
use Backend\Form\Type\DeleteType;
use Backend\Modules\Catalog\Domain\CartRule\CartRule;
use Backend\Modules\Catalog\Domain\CartRule\Command\DeleteCartRule as DeleteCommand;
use Backend\Modules\Catalog\Domain\CartRule\Event\CartRuleDeleted;
use Backend\Modules\Catalog\Domain\CartRule\Exception\CartRuleNotFound;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

class DeleteCartRule extends BackendBaseActionDelete
{
    /**
     * Execute the action
     */
    public function execute(): void
    {
        $deleteForm = $this->createForm(DeleteType::class, null, ['module' => $this->getModule()]);
        $deleteForm->handleRequest($this->getRequest());
        if ( ! $deleteForm->isSubmitted() || ! $deleteForm->isValid()) {
            $this->redirect(BackendModel::createUrlForAction('Index', null, null, ['error' => 'non-existing']));

            return;
        }
        $deleteFormData = $deleteForm->getData();

        $cartRule = $this->getCartRule((int)$deleteFormData['id']);

        try {
            // The command bus will handle the saving of the content block in the database.
            $this->get('command_bus')->handle(new DeleteCommand($cartRule));

            $this->get('event_dispatcher')->dispatch(
                CartRuleDeleted::EVENT_NAME,
                new CartRuleDeleted($cartRule)
            );

            $this->redirect($this->getBackLink(['report' => 'deleted', 'var' => $cartRule->getTitle()]));
        } catch (ForeignKeyConstraintViolationException $e) {
            $this->redirect($this->getBackLink(['error' => 'products-connected']));
        }
    }

    private function getBackLink(array $parameters = []): string
    {
        return BackendModel::createUrlForAction(
            'CartRules',
            null,
            null,
            $parameters
        );
    }

    private function getCartRule(int $id): CartRule
    {
        try {
            return $this->get('catalog.repository.cart_rule')->findOneByIdAndLocale(
                $id,
                Locale::workingLocale()
            );
        } catch (CartRuleNotFound $e) {
            $this->redirect($this->getBackLink(['error' => 'non-existing']));
        }
    }
}
