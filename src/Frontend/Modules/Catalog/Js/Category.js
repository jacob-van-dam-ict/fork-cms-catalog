$(function(){
    var filters = {},
        productOverviewSelector = '.overview',
        page = 1,
        sort = null;

    function updateFilters(pageUpdate) {
        // Reset our current filters
        filters = {};

        // Reset the page
        if (!pageUpdate) {
            page = 1;
        }

        // Set the sort order
        sort = $('[data-sort]').val();

        $('[data-filter]:checked').each(function(){
            if (!filters.hasOwnProperty($(this).data('filter'))) {
                filters[$(this).data('filter')] = [];
            }

            filters[$(this).data('filter')].push($(this).data('filterValue'));
        });

        updateUrl();
        filterProducts();
    }

    function updateUrl() {
        var queryParts = [],
            url = jsData['Catalog']['categoryUrl'];

        // Convert our filters to a normal url
        $.each(filters, function(key, values){
            queryParts.push(key +'='+ values.join(','));
        });

        queryParts.push(jsFrontend.locale.lbl('Page') +'='+ page);
        queryParts.push('sort='+ sort);

        // Add query parts when there are
        if (queryParts.length > 0) {
            url += '?' +  queryParts.join('&');
        }

        // Return the url
        history.pushState(
            {
                filters : filters,
                page : page,
                sort : sort
            },
            '',
            url
        );

        return url;
    }

    /**
     * Start filtering our products
     */
    function filterProducts() {
        // Some defaults are ok but others need a rewrite
        $.ajax({
            data : {
                fork : {
                    module : 'Catalog',
                    action : 'FilterProducts'
                },
                filters : filters,
                category : jsData['Catalog']['category'],
                page : page,
                sort : sort
            }
        }).done(function(response){
            // Append the products
            $(productOverviewSelector).empty()
                .append(response['data']['products']);

            // Build pagination HTML
            var pagination = response['data']['pagination'],
                paginationHolder = $('.overview-nav'),
                ul = $('<ul>');

            // Build previous
            if (pagination['showPrevious']) {
                $('<li>').append(
                    $('<a>').attr({
                            href : pagination['urlPrevious'],
                            'data-page': pagination['previousNumber']
                        })
                        .data('page', pagination['previousNumber'])
                        .html(utils.string.ucfirst(jsFrontend.locale.lbl('Previous')))
                ).appendTo(ul);
            } else {
                $('<li>').addClass('disabled')
                    .append(
                        $('<a>').attr('href', 'javascript:void(0);')
                            .html(utils.string.ucfirst(jsFrontend.locale.lbl('Previous')))
                    )
                    .appendTo(ul);
            }

            // Build pagination
            $('<li>').html(pagination['currentPage'] +' / '+ pagination['pageCount'])
                .appendTo(ul);

            // Build next button
            if (pagination['showNext']) {
                $('<li>').append(
                    $('<a>').attr({
                        href : pagination['urlNext'],
                        'data-page': pagination['nextNumber']
                    })
                        .data('page', pagination['nextNumber'])
                        .html(utils.string.ucfirst(jsFrontend.locale.lbl('Next')))
                ).appendTo(ul);
            } else {
                $('<li>').addClass('disabled')
                    .append(
                        $('<a>').attr('href', 'javascript:void(0);')
                            .html(utils.string.ucfirst(jsFrontend.locale.lbl('Next')))
                    )
                    .appendTo(ul);
            }

            // Set HTML
            paginationHolder.html(ul);
        });
    }

    // Handle the filter change
    $('[data-filter]').change(function(){
        updateFilters();
    });

    // Handle the sorting
    $('[data-sort]').change(function(){
        updateFilters();
    });

    // Handle the back click in the browser
    $(window).bind('popstate', function(e){
        filters = e.originalEvent.state.filters;
        page = e.originalEvent.state.page;
        sort = e.originalEvent.state.sort;

        // Reset current filters
        $('[data-filter][data-filter-value]').prop('checked', false);

        // Update the filters
        $.each(filters, function(key, values){
            $.each(values, function(i, value){
                $('[data-filter="'+ key +'"][data-filter-value="' + value + '"]').prop('checked', true);
            });
        });

        // Set the sort order
        $('[data-sort]').val(sort);

        // Start the filter
        filterProducts();
    });

    // Pagination click
    $(document).on('click', '[data-page]', function(e){
        e.preventDefault();

        page = $(this).data('page');

        updateFilters(true);
    });

    // Update the selected filters based on the request query
    var query = window.location.search;
    if (query) {
        query = query.replace('?', '');

        var groups = query.split('&');

        $.each(groups, function(i, group){
            group = group.split('=');

            var key = group[0],
                values = group[1].split(',');

            $.each(values, function(j, value){
                $('[data-filter="'+ key +'"][data-filter-value="' + value + '"]').prop('checked', true);
            });
        });
    }

    // Show and hide filters
    $('.show-more').click(function(e){
        e.preventDefault();

        var filterElement = $($(this).attr('href'));

        if (filterElement.data('more')) {
            filterElement.data('more', false);
            filterElement.slideUp();

            $(this).html($(this).data('showMore'));
        } else {
            filterElement.data('more', true);
            filterElement.slideDown();

            $(this).html($(this).data('showLess'));
        }
    });
});
