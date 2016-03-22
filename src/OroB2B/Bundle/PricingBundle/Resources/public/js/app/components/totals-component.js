define(function(require) {
    'use strict';

    var TotalsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var localeSettings = require('orolocale/js/locale-settings');

    /**
     * @export orob2bpricing/js/app/components/totals-component
     * @extends oroui.app.components.base.Component
     * @class orob2bpricing.app.components.TotalsComponent
     */
    TotalsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            url: '',
            selectors: {
                form: '',
                template: '#totals-template',
                subtotals: '[data-totals-container]'
            },
            method: 'POST',
            events: []
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $method: null,

        /**
         * @property {jQuery}
         */
        $subtotals: null,

        /**
         * @property {Object}
         */
        template: null,

        /**
         * @property {String}
         */
        formData: '',

        /**
         * @property {String}
         */
        eventName: '',

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @property {Array}
         */
        items: [],

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            if (this.options.url.length === 0) {
                return;
            }

            this.$el = options._sourceElement;
            this.$form = $(this.options.selectors.form);
            this.$method = this.options.method;
            this.$subtotals = this.$el.find(this.options.selectors.subtotals);
            this.template = _.template($(this.options.selectors.template).text());
            this.loadingMaskView = new LoadingMaskView({container: this.$el});
            this.eventName = 'total-target:changing';

            this.updateTotals();
            this.initializeListeners();
        },

        initializeListeners: function() {
            mediator.on('line-items-totals:update', this.updateTotals, this);
            mediator.on('update:account', this.updateTotals, this);
            mediator.on('update:website', this.updateTotals, this);
            mediator.on('update:currency', this.updateTotals, this);

            var self = this;
            _.each(this.options.events, function(event) {
                mediator.on(event, self.updateTotals, self);
            });
        },

        showLoadingMask: function() {
            this.loadingMaskView.show();
        },

        hideLoadingMask: function() {
            this.loadingMaskView.hide();
        },

        /**
         * Get and render subtotals
         */
        updateTotals: function(e) {
            this.showLoadingMask();

            if (this.getTotals.timeoutId) {
                clearTimeout(this.getTotals.timeoutId);
            }

            this.getTotals.timeoutId = setTimeout(_.bind(function() {
                this.getTotals.timeoutId = null;

                var promises = [];
                mediator.trigger(this.eventName, promises);

                if (promises.length) {
                    $.when.apply($, promises).done(_.bind(this.updateTotals, this, e));
                } else {
                    this.getTotals(_.bind(function(subtotals) {
                        this.hideLoadingMask();
                        if (!subtotals) {
                            return;
                        }
                        this.render(subtotals);
                    }, this));
                }
            }, this), 100);
        },

        /**
         * Get order subtotals
         *
         * @param {Function} callback
         */
        getTotals: function(callback) {
            if (this.$method === 'GET') {
                $.get(this.options.url, function (response) {
                    callback(response);
                });
                return;
            }

            var formData = this.$form.find(':input[data-ftid]').serialize();

            if (formData === this.formData) {
                callback();//nothing changed
                return;
            }

            this.formData = formData;

            var self = this;
            $.post(this.options.url, formData, function(response) {
                if (formData === self.formData) {
                    //data doesn't change after ajax call
                    var totals = response || {};
                    callback(totals);
                }
            });
        },

        /**
         * Render totals
         *
         * @param {Object} totals
         */
        render: function(totals) {
            this.items = [];

            _.each(totals.subtotals, _.bind(this.pushItem, this));

            this.pushItem(totals.total);

            this.$subtotals.html(_.filter(this.items).join(''));

            this.items = [];
        },

        /**
         * @param {Object} item
         */
        pushItem: function(item) {
            var localItem = _.defaults(
                item,
                {
                    amount: 0,
                    currency: localeSettings.defaults.currency,
                    visible: false,
                    template: null,
                    data: {}
                }
            );

            if (localItem.visible === false) {
                return;
            }

            item.formattedAmount = NumberFormatter.formatCurrency(item.amount, item.currency);

            var renderedItem = null;

            if (localItem.template) {
                renderedItem = _.template(item.template)({item: item});
            } else {
                renderedItem = this.template({item: item});
            }

            this.items.push(renderedItem);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.items;

            mediator.off('line-items-totals:update', this.updateTotals, this);
            mediator.off('update:account', this.updateTotals, this);
            mediator.off('update:website', this.updateTotals, this);
            mediator.off('update:currency', this.updateTotals, this);
            var self = this;
            _.each(this.options.events, function(event) {
                mediator.off(event, self.updateTotals, self);
            });
            TotalsComponent.__super__.dispose.call(this);
        }
    });

    return TotalsComponent;
});
