define(function(require) {
    'use strict';

    var CheckboxInputWidget;
    var $ = require('jquery');
    var _ = require('underscore');
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');

    CheckboxInputWidget = AbstractInputWidget.extend({
        widgetFunction: function() {
            this.findContainer();
            this.getContainer().on('keydown keypress', _.bind(function (event) {
                if (event.which === 13) {
                    this._toggle();
                }
            }, this));


            this.$el.on('change', _.bind(this._toggle, this));
            //    //if ($(this).attr('checked') !== 'checked' || typeof $(this).attr('checked') === 'undefined') {
            //        this._on();
            //    } else {
            //        this._off();
            //    }
            //});
        },

        _toggle: function () {
            var $content = $('[data-checkbox-triggered-content]');
            if (this.$el.prop('checked')) {
                this._on();
                $content.show();
            } else {
                this._off();
                $content.hide();
            }
        },

        _on: function () {
            this.$el.attr('checked', true);
            this.$el.prop('checked', 'checked');
            this.$el.parent().addClass('checked');
        },

        _off: function () {
            this.$el.attr('checked', false);
            this.$el.removeProp('checked');
            this.$el.parent().removeClass('checked');
        },

        findContainer: function() {
            this.$container = this.$el.closest('label');
        }
    });

    return CheckboxInputWidget;
});
