import './acl';
import './service';
import './component/hug-mail-variable-picker';
import './component/hug-mail-compose-modal';
import './component/hug-mail-history-grid';
import './component/hug-mail-tab-content';
import './component/hug-mail-document-type-mapping';
import './module/hug-mail-cockpit';
import './extension/sw-order-detail';
import './extension/sw-customer-detail';
import './extension/sw-order-document-card';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Locale.extend('de-DE', deDE);
Shopware.Locale.extend('en-GB', enGB);
