define(["uiComponent", "ko", "mage/translate", "moment", "mage/url"], function (
    Component,
    ko,
    $t,
    moment,
    url
) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Ecommet_SmartDeliveryDate/delivery-date-info",
        },
        isVisible: ko.observable(false),
        deliveryDates: ko.observableArray([]),
        timeLimit: ko.observable(""),
        cutoffTime: ko.observable(""),
        countdown: ko.observable(""),
        message: ko.observable(""),
        intervalId: null,

        initialize: function () {
            this._super();
            this.fetchDeliveryDates();
        },

        formatDate: function (date) {
            return date && date.label ? date.label : "";
        },

        startCountdown: function (cutoffTime) {
            const self = this;
            const update = () => {
                const now = moment();
                const cutoff = moment(cutoffTime);

                if (cutoff.isBefore(now)) {
                    self.fetchDeliveryDates();
                    clearInterval(self.intervalId);
                    return;
                }

                const duration = moment.duration(cutoff.diff(now));
                const formatted = `${duration.hours()}h ${duration.minutes()}m ${duration.seconds()}s`;

                self.message(
                    `Hurry! Order within ${formatted} for fastest delivery`
                );
            };

            update();
            self.intervalId = setInterval(update, 1000);
        },

        fetchDeliveryDates: function () {
            const self = this;
            const url = this.ajaxUrl;

            fetch(url, {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        self.isVisible(true);
                        self.deliveryDates(data.deliveryDates || []);

                        if (!self.showTimeLimit) {
                            self.message($t("Order now and get it"));
                            return;
                        }

                        self.cutoffTime(data.cutoffTime);
                        self.timeLimit(data.timeLimit);
                        self.startCountdown(data.cutoffTime);
                    } else {
                        self.isVisible(false);
                        console.error("Error fetching delivery dates:", data);
                    }
                })
                .catch((error) => {
                    self.isVisible(false);
                    console.error("Fetch error:", error);
                });
        },
    });
});
