angular.module('starter')
//config param of App
.constant('appConfig', {

    DOMAIN_URL: 'http://200.112.210.132:84',
	ADMIN_EMAIL: 'jedmacmahonve@unal.edu.co',
        
	CLIENT_ID_AUTH0: 'H62fCSFGxrbKTRwArIJRdRjuhFnscgNo',
	DOMAIN_AUTH0: 'khanhtt.auth0.com',
        
	ENABLE_FIRST_LOGIN: false,
	
	ENABLE_THEME: 'topgears',
	
	ENABLE_PUSH_PLUGIN: false,
	ENABLE_PAYPAL_PLUGIN: false,
	ENABLE_STRIPE_PLUGIN: false,
	ENABLE_RAZORPAY_PLUGIN: false,
	ENABLE_MOLLIE_PLUGIN: false,
	ENABLE_OMISE_PLUGIN: false
	}
)


//dont change this value if you dont know what it is
.constant('appValue', {
	// API_URL: '/module/icymobi/', //for prestashop platform
    API_URL: '/is-commerce/api/', //for worpdress and magento platform
	API_SUCCESS: 1,
	API_FAILD: -1
})


//list language
.constant('listLanguage', [
            {code: 'en', text: 'English'},
            {code: 'fr', text: 'French'},
	]
)
;
(function () {
	new WOW().init();
});
