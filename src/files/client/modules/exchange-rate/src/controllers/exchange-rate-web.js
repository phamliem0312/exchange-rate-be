define('exchange-rate:controllers/exchange-rate-web', ['controller'], function (RecordController) {
    return class ExchangeRateController extends RecordController {
        actionWebView(){
            this.createWebView();
        }
        
        createWebView() {
            
            const view = 'exchange-rate:views/exchange-rate-web';

            this.entire(view, {});
        }
    }
});
