const ApiService = Shopware.Classes.ApiService;

/**
 * Client for the plugin's admin API routes (/api/_action/hug-mail-cockpit/*).
 */
export default class HugMailCockpitApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'hug-mail-cockpit') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'hugMailCockpitApiService';
    }

    /**
     * Variable picker data + recipient prefill for an order or customer.
     */
    getMailContext({ orderId = null, customerId = null }) {
        return this.httpClient
            .get(`_action/${this.getApiBasePath()}/variables`, {
                params: { orderId, customerId },
                headers: this.getBasicHeaders(),
            })
            .then(ApiService.handleResponse);
    }

    /**
     * Server-side render of an unmodified mail template against the order/
     * customer context ("render, then edit" flow).
     */
    renderTemplate(payload) {
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/render-template`, payload, {
                headers: this.getBasicHeaders(),
            })
            .then(ApiService.handleResponse);
    }

    preview(payload) {
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/preview`, payload, {
                headers: this.getBasicHeaders(),
            })
            .then(ApiService.handleResponse);
    }

    send(payload) {
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/send`, payload, {
                headers: this.getBasicHeaders(),
            })
            .then(ApiService.handleResponse);
    }

    getHistory({ orderId = null, customerId = null }) {
        return this.httpClient
            .get(`_action/${this.getApiBasePath()}/history`, {
                params: { orderId, customerId },
                headers: this.getBasicHeaders(),
            })
            .then(ApiService.handleResponse);
    }
}
