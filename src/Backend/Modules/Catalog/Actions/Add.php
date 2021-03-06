<?php

namespace Backend\Modules\Catalog\Actions;

use Backend\Core\Engine\Base\ActionAdd as BackendBaseActionAdd;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Language\Locale;
use Backend\Modules\Catalog\Domain\Product\Command\CreateProduct;
use Backend\Modules\Catalog\Domain\Product\Event\Created;
use Backend\Modules\Catalog\Domain\Product\Product;
use Backend\Modules\Catalog\Domain\Product\ProductType;
use Symfony\Component\Form\Form;

/**
 * This is the add-action, it will display a form to create a new product
 *
 * @author Tim van Wolfswinkel <tim@webleads.nl>
 * @author Jacob van Dam <j.vandam@jvdict.nl>
 */
class Add extends BackendBaseActionAdd
{
    /**
     * Execute the actions
     */
    public function execute(): void
    {
        parent::execute();

        $form = $this->getForm();
        if ( ! $form->isSubmitted() || ! $form->isValid()) {
            $this->template->assign('form', $form->createView());

            $this->parse();
            $this->display();

            return;
        }

        $createProduct = $this->createProduct($form);

        $this->get('event_dispatcher')->dispatch(
            Created::EVENT_NAME,
            new Created($createProduct->getProductEntity())
        );

        $this->redirect(
            $this->getBackLink(
                [
                    'report'    => 'added',
                    'var'       => $createProduct->title,
                    'highlight' => 'row-' . $createProduct->getProductEntity()->getId(),
                ]
            )
        );
    }

    protected function parse(): void
    {
        parent::parse();

        $this->header->addJS(
            '/js/vendors/select2.full.min.js',
            null,
            true,
            true
        );

        $this->header->addJS(
            '/js/vendors/' . Locale::workingLocale() . '.js',
            null,
            true,
            true
        );

        $this->header->addJS('Select2Entity.js');
        $this->header->addJS('ProductDimensions.js');

        $this->header->addCSS(
            '/css/vendors/select2.min.css',
            null,
            true,
            false
        );
        $this->header->addCSS('ProductDimensions.css');

        $this->header->addJsData($this->getModule(), 'types', [
            'default' => Product::TYPE_DEFAULT,
            'dimensions' => Product::TYPE_DIMENSIONS,
        ]);
    }

    private function createProduct(Form $form): CreateProduct
    {
        $createProduct = $form->getData();

        // The command bus will handle the saving of the category in the database.
        $this->get('command_bus')->handle($createProduct);

        return $createProduct;
    }

    private function getBackLink(array $parameters = []): string
    {
        return BackendModel::createUrlForAction(
            'Index',
            null,
            null,
            $parameters
        );
    }

    private function getForm(): Form
    {
        $form = $this->createForm(
            ProductType::class,
            new CreateProduct(),
            [
                'categories' => $this->get('catalog.repository.category')->getTree(Locale::workingLocale())
            ]
        );

        $form->handleRequest($this->getRequest());

        return $form;
    }
}
