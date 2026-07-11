import HugMailCockpitApiService from './hug-mail-cockpit.api.service';

Shopware.Application.addServiceProvider('hugMailCockpitApiService', () => {
    const initContainer = Shopware.Application.getContainer('init');

    return new HugMailCockpitApiService(initContainer.httpClient, Shopware.Service('loginService'));
});
