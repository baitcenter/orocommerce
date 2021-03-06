define(['underscore', 'orolocale/js/formatter/number', 'orolocale/js/locale-settings'],
    function(_, NumberFormatter, localeSettings) {
        'use strict';

        /**
         * Tax Formatter
         *
         * @export orotax/js/formatter/tax
         * @name   orotax.formatter.tax
         */
        const taxFormatter = function() {
            const formatElement = function(value, currency) {
                if (_.isUndefined(currency)) {
                    currency = localeSettings.getCurrency();
                }

                if (_.isNaN(parseFloat(value))) {
                    return value;
                }

                return NumberFormatter.formatCurrency(value, currency);
            };

            const formatPercent = function(value) {
                if (value.indexOf('%') > -1) {
                    return value;
                }

                return NumberFormatter.formatPercent(value);
            };

            return {
                /**
                 * @param {Object} item
                 */
                formatItem: function(item) {
                    const localItem = _.extend({
                        includingTax: 0,
                        excludingTax: 0,
                        taxAmount: 0,
                        currency: localeSettings.getCurrency()
                    }, item);

                    return {
                        includingTax: formatElement(localItem.includingTax, localItem.currency),
                        excludingTax: formatElement(localItem.excludingTax, localItem.currency),
                        taxAmount: formatElement(localItem.taxAmount, localItem.currency)
                    };
                },

                /**
                 * @param {Object} item
                 */
                formatTax: function(item) {
                    const localItem = _.extend({
                        taxAmount: 0,
                        taxableAmount: 0,
                        rate: 0,
                        tax: '',
                        currency: localeSettings.getCurrency()
                    }, item);

                    return {
                        tax: localItem.tax,
                        taxAmount: formatElement(localItem.taxAmount, localItem.currency),
                        taxableAmount: formatElement(localItem.taxableAmount, localItem.currency),
                        rate: formatPercent(localItem.rate)
                    };
                },

                /**
                 * @param {String} value
                 * @param {String} currency
                 */
                formatElement: function(value, currency) {
                    return formatElement(value, currency);
                }
            };
        };

        return taxFormatter();
    });
