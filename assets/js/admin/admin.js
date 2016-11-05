/**
 * Admin Scripts
 *
 * @package book-database
 * @copyright Copyright (c) 2016, Ashley Gibson
 * @license GPL2+
 */

(function ($) {

    var Book_Database = {

        /**
         * Initialize stuff
         */
        init: function () {
            this.sort();

            $('.bookdb-book-option-toggle').click(this.toggleBookTextarea);
            $('#bookdb-book-layout-cover-changer').change(this.changeCoverAlignment);

            console.log('ini');
        },

        /**
         * Sort
         */
        sort: function() {
            $('.bookdb-sortable').sortable({
                cancel: '.bookdb-no-sort, textarea, input, select',
                connectWith: '.bookdb-sortable',
                placeholder: 'bookdb-sortable-placeholder',
                update: function (event, ui) {
                    var currentItem = ui.item;
                    var parentID = currentItem.parent().attr('id');
                    var disabledIndicator = currentItem.find('.bookdb-book-option-disabled');
                    if ($('#' + parentID).hasClass('bookdb-sorter-enabled-column')) {
                        disabledIndicator.val('false');
                    } else {
                        disabledIndicator.val('true');
                    }
                }
            }).enableSelection();
        },

        /**
         * Open up editable textarea.
         *
         * @param e
         */
        toggleBookTextarea: function (e) {
            $(this).next().slideToggle();
        },

        /**
         * Change cover alignment.
         *
         * @param e
         */
        changeCoverAlignment: function (e) {
            var parentDiv = $('#bookdb-book-option-cover');
            parentDiv.removeClass(function (index, css) {
                return (css.match(/(^|\s)bookdb-book-cover-align-\S+/g) || []).join(' ');
            });
            parentDiv.addClass('bookdb-book-cover-align-' + $(this).val());
        }

    };

    Book_Database.init();

})(jQuery);