30-Jan-2018
   - Version 4.2.6
       - Bug Fixes:
           * Wrong new order status on Server
           
23-Jan-2018
   - Version 4.2.5
       - Improvements:
           * Client IP
           * Compatibility with MageMaven
           * Invalid License message for licenses with spaces
           * Token configuration
           
       - Bug Fixes:
           * CRYPT_RIJNDAEL constructor warning
           * Comments not showing on email (Server MOTO)
           * Removed surcharges support. (SagePay no longer allows this)
           * Browser back button re-sending email on  mobile
           * Direct is now disabled on code to make the module safer. (WARNING: Contact us if you are using direct)
           * Wrong first order status
               
5-Oct-2017
   - Version 4.2.4
       - Improvements:
           * Token configurations behaviour
           * Replace mcrypt for phpseclib
           * Trim license key spaces
       - Bug Fixes:
           * Expiry date not saving
           * Continue not working when token whit some token descriptions
           * Email wiped from the quote when buying a virtual product using Paypal
           * Direct not saving transactions
           * GetUrl with integration code empty
           
13-Jul-2017
   - Version 4.2.3
       - Improvements:
           * Compatibility for Master Card 2-series BIN
           * Index added to the transaction table to increase performance
       - Bug Fixes:
           * Updated create account link
           * Workaround for payment taken after "Please specify shipping method" error
           * Verify cart consistency was voiding the some unrelated transactions
           * Payment action setting on SERVER MOTO
           * Surcharges file was missing from modman

10-Mar-2017
   - Version 4.2.2
        - Improvements:
            * Circle improvements
            * Coding standards
            * Token name validations to avoid adding CC number by mistake
        - Bug fixes:
            * Error when logging "Payment has failed" was hiding the error.
            * Refund error when using Authenticate

10-Mar-2017
   - Version 4.2.1
       - Improvements:
           * Coding standards
                
16-Feb-2017
   - Version 4.2.0
       * Bug fixes:
            - VPSProtocol and TxAuthNo not being saved resulting on "Error(3096): The VPSProtocol value is required.”
            - Edit token description on customer side
            - Order email not showing CC and last four digits
            - saveAction bug
            - Hardcoded fraud table name
            - Form integration not showing correctly on order view.
            - Typos
       * Improvements:
            - Allowed countries feature for PayPal integration
            - New order status feature for Repeat MOTO integration

2-Dic-2016
   - Version 4.1.9
       * Bug fixes:
            - Some deferred orders not showing on deferred order grid
            - Server configs showing when disabled
            - Security patch
       * Improvements:
            - New coding standard

12-Oct-2016
   - Version 4.1.8
       * Improvements:
            - Support link updated to the new ticket system's email
            - Coding standard
            - Don't expose admin url on frontend.
            - Checkout no longer breaks without sandbox.xml
            - Typo fixes
       * Bug fixes:
            - Update ReD status when calling getTransactionDetail via Reporting API.
            - Workaround for prototype issue causing "Payment has failed error"
            - Fix class name, it does not work on case-sensitive filesystems.
            - Fix invalid chars on incide parameters.
                
22-Aug-2016
  - Version 4.1.7
    * Improvement: Coding standard improvements

18-Aug-2016
  - Version 4.1.6
     * Improvement: Removed unreachable code after return statement
     * Improvement: Ebizmarts store url is now https.
     * Improvement: str_replace compatibility.
     * Improvement: Added ReferrerID to PI
     * Bug fix: Transaction not showing on Orphan list after editing.
     * Bug fix: Shipping address issue when quote contains only virtual products.
     * Bug fix: Code injection fix for tokens

12-Jul-2016
  - Version 4.1.5
    * Bug fix: Recurring error when Save order before was enabled.
    * Bug fix: MageMonkey issues.
    * Bug fix: Typo on error helper.
    * Bug fix: Missing translations on error helper.


18-May-2016
  - Version 4.1.4
      * Improvement: SERVER with "Save order before" not recovering quote if browser back button clicked during payment pages.
      * Improvement: Updated syntax on some js scripts to avoid conflicts (thanks Tom Robertshaw for this).
      * Improvement: Order details in Success URL of SERVER are now optional.
      * Improvement: Support form now uses default mail client instead of a form.
      * Improvement: Expiry date in transaction modal better format.
      * Bug fix: "Save order before" setting in MOTO using frontend config instead.
      * Bug fix: SERVER MOTO with DEFER and SAVE ORDER BEFORE not completing payment.
      * Bug fix: "Empty" function error in Sales Observer with some PHP versions.
      * Bug fix: Token controller now validating customer session.
      * Bug fix: Item SKU not being correctly parsed on VPTXID view.

10-Mar-2016
  - Version 4.1.3
      * COMPATIBLE WITH MAGENTO SECURITY PATCHES SUPEE-7405 v1.1.
      * New Feature: 3D secure rules settings added for SERVER integration.
      * Improvement: Paypal "processing payment..." page added to avoid white screen during processing.
      * Improvement: Customer XML failsafe in case bad format or invalid chars.
      * Bug fix: Can't checkout using store credit in Magento EE if default payment method is Sage Pay.
      * Bug fix: Surcharges not working as expected with SERVER MOTO.
      * Bug fix: Added website restrictions to SERVER controller to avoid issues with EE private sales.
      * Bug fix: Redirecting incorrectly when customer REJECTED by consecutive failures.
      * Bug fix: Korean currency stopped accepting decimals.
      * Bug fix: Token saved as customerId 0 if using SERVER and "save order before" is enabled.
      * Bug fix: Token description not getting saved in customer area using SERVER integration.

21-Jan-2016
  - Version 4.1.2
      * COMPATIBLE WITH MAGENTO SECURITY PATCHES SUPEE-7405 & SUPEE-7616.
      * New Feature: SERVER Pre-save order cron configuration to automatically cancel pending payment orders.
      * Improvement: Don't allow success page to reload session more than once.
      * Bug fix: CC expiration date not being showed in backend when token is used.
      * Bug fix: Get shipping method instead of description for virtual product validation.
      * Bug fix: Cancel pending payment cron trying to void transactions.
      * Bug fix: Modman file errors.
      * Bug fix: Main window redirect from iframe issue with DIRECT 3D.
      * Bug fix: Back button on orphan transaction edit form not working.
      * Bug fix: Vendorname was being forced to lowercase.
      * Bug fix: Check that the amounts match error when transaction currency base && currency switcher is used.
      * Bug fix: MCDEBIT was not validating.

04-Dic-2015
  - Version 4.1.1
      * New Feature: SERVER now has the possibility to pre-save the order to avoid session lost issues with SSL.
      * Improvement: Removed email from SERVER MOTO NotificationURL to prevent exceeding the 255 char limit.
      * Improvement: Added validation to prevent customers from sending the CC number in the CC owner field.
      * Bug fix: SERVER security vulnerability.
      * Bug fix: CURL_VERIFY_PEER default to true and fixed some areas where it was not taking the configuration.
      * Bug fix: ApplyAVSCV2 typo on SERVER integration.
      * Bug fix: In rare occasions there was a space added to the country code being sent which resulted in a checkout error.
      * Bug fix: SERVER issues when order saving process taking more than 20 secs.
      * Bug fix: Invoicing and capturing online for 0 amount no longer allowed.
      * Bug fix: Force 3D secure on selected card setting not working as expected.
      * Bug fix: Token list with SERVER still hidden after switching back and forth to the "Add new card/Use saved card".

29-Oct-2015
  - Version 4.0.1
      * SUPPORT FOR MAGENTO SECURITY PATCH SUPEE-6788.
      * New Feature: Support for Sage Payâ€™s New Integration Type PI (Beta).
      * Bug fix: Original price of items showing as $0 with SERVER MOTO when using discount.
      * Bug fix: Order comments not working on SERVER with OSC and Magemaven.
      * Bug fix: Modman breaking local folders.
      * Bug fix: Token not using AVSCV2 settings.
      * Improvement: Workaround to prevent landing on empty cart page when using SERVER after order saving delays.

12-Oct-2015
  - Version 3.0.28.4
      * New Feature: Modman support.
      * New Feature: Option to use SERVER with 'full redirect' for adding tokens from customer account.
      * Improvement: Improved stability to avoid un-invoiced orders with SERVER.
      * Bug fix: Auto-fulfill orders from cron not working properly.
      * Bug fix: Cancelling a payment on mobile redirecting to a blank screen on mobile.
      * Bug fix: SERVER integration broken when using multi-shipping after magento security patch.
      * Bug fix: XML basket not working as expected with guest customers.
      * Bug fix: Profiler not working on backend.
      * Bug fix: Repeat MOTO search grid not working as expected.
      * Bug fix: Prevent invoicing feature not working with SERVER.
      * Bug fix: Whitelist IP on backend always showing error at the bottom.
      * Bug fix: RED fraud badge images not showing correctly.
      * Bug fix: Paypal invalid basket error workaround.
      * Bug fix: Sage 50 basket sending wrong value if invoice set to "Including Taxes".
      * Bug fix: Workaround for IE 10 bug when using any iframe.

28-Jul-2015
  - Version 3.0.26.7
      * Improvement: Support form on backend now using secure connection.
      * Improvement: Workaround to prevent invoices failing on some orders due to session lost.
      * Bug fix: Total showing wrong when using multi-currency with SERVER.
      * Bug fix: Whitelist IP stopped working after routers modification.
      * Bug fix: SERVER always using saved token if OSC module is being used.
      * Bug fix: Paypal express force shipping estimate skipped for virtual products.
      * Bug fix: Multiple error messages on success due to session issues.
      * Bug fix: Paypal refunds and releases not working properly after magento patches.
      * Bug fix: OSC causes error when logged in users change their billing address.

08-Jul-2015
  - Version 3.0.26.5
      * Improvement: Permissions added for backend controllers to fix magento patch issue.

12-Jun-2015
  - Version 3.0.26.4
      * New Feature: Added config option to prevent automatic invoicing with authorize and capture.
      * Improvement: IPv6 beta support added.
      * Improvement: Thirdman cron improved.
      * Improvement: Minor changes to improve support for some 3rd party checkout modules.
      * Improvement: Use magento admin router for security, admin controller calls are now hidden.
      * Improvement: Expired tokens are now deleted on customer login.
      * Bug fix: Add sales->sagepay->configuration to ACL.
      * Bug fix: VOID transaction not working in orphan grid.
      * Bug fix: PayPal checkout button was being rendered even if total was below min order.
      * Bug fix: PayPal taking DIRECT 'place order status' configuration.
      * Bug fix: Checkout sidebar payment method section broken on newer php versions.
      * Bug fix: Typo on sql query affecting new installs.
      * Bug fix: Token not being saved when using SERVER with SSL after latest Magento patch.

08-Apr-2015
  - Version 3.0.24.4
      * New Feature: CURL proxy configuration.
      * New Feature: VendorTxCode prefix can now be configured.
      * Improvement: API error response no longer generating magento exception
      * Improvement: Billing state is now sent for all countries, limited to 2 chars to avoid issues.
      * Bug fix: Send email config not working.
      * Bug fix: Mass 3rd man check not auto invoicing.
      * Bug fix: Adding token card through DIRECT on customer area was causing issues if cc details were wrong.
      * Bug fix: Surcharges block causing minor issues if surcharges are disabled.
      * Bug fix: Order email not showing CC details in some occasions.
      * Bug fix: Typo on CustomerId.php.
      * Bug fix: SERVER MOTO redirecting to frontend on fail.

04-Mar-2015
  - Version 3.0.24.3
      * New Feature: Paypal improved logos and added functionality to force XML basket.
      * New Feature: Paypal express now has an option to avoid second review step.
      * New Feature: Paypal Billing Agreement now supported.
      * New Feature: Orphan grid improved.
      * New Feature: Error parser (initial stage).
      * New Feature: No longer require SID on Frontend.
      * New Feature: CURLOPT_SSL_VERSION value can now be specified from config.
      * New Feature: Validate quote now optional.
      * New Feature: Surcharges module is now separated from suite to avoid possible tax issues.
      * New Feature: Fraud Information grid can now run 3rd man checks manually and added 3rd man check log.
      * New Feature: Added a setting for skipping AVSCV2 for overseas orders.
      * New Feature: DB INDEX created in transactions table to increase FRAUD checks performance.
      * Improvement: Workaround for security fault on SERVER success.
      * Improvement: SERVER fail page improved.
      * Improvement: Modal css corrections to avoid bootstrap conflicts.
      * Improvement: JsTranslator improved messages.
      * Bug fix: Customer session lost when using SERVER on Magento 1.9.1+.
      * Bug fix: Order not being saved if 3d fails and is allowed to fail.
      * Bug fix: Basket XML format fix.
      * Bug fix: Tracker generates a warning when accessing config area.
      * Bug fix: Add card on customer area not working.
      * Bug fix: Checkout review sidebar showing old CC details after re-selecting Paypal as payment method.
      * Bug fix: Sweet Tooth support fixed after magento 1.9.1 upgrade.
      * Bug fix: Log now validates filename for strange chars.

16-Dic-2014
  - Version 3.0.22.1
      * Improvement: SSL for backend logo images.
      * Improvement: Paypal express force shipping config option.
      * Bug fix: Surcharge taxes not working with percent amounts.
      * Bug fix: Session lost generating "Please specify a shipping method" error after 1.9.1 upgrade.
      * Bug fix: Remember token script issue.
      * Bug fix: Euro Payments pending status for orders.
      * Bug fix: JS bug affecting earlier Magento versions when on one page checkout trying to proceed to payment section.
      * Bug fix: Safe call to getEdition, it was causing the config page to load a blank page on earlier Magento versions.

12-Nov-2014
  - Version 3.0.22
      * New feature: Tokens can be assigned a nickname.
      * New feature: Euro Payments PENDING support (beta).
      * Improvement: Surcharge enable/disable functionality.
      * Improvement: Dashboard tab moved to sales->Sage Pay.
      * Improvement: Added direct link to configuration from sales->Sage Pay tab.
      * Improvement: Orphan recover functionality made stable, working for all default product types.
      * Bug fix: Wrong error message displayed on MOTO server payment.
      * Bug fix: Set default credit card.
      * Bug fix: Direct MOTO integration wrongly validating accepted card types.
      * Bug fix: Redirects the user to cart when error in transaction (happening with certain errors).
      * Bug fix: Some events not being triggered when cards added by DIRECT integration on user panel.
      * Bug fix: Customer_note_notify not being passed from quote to order due a conflict with our code.
      * Bug fix: Duplicate tokens on some cases.
      * Bug fix: Preventing token to be stored on DIRECT and SERVER integrations when some values are repeated.
      * Bug fix: Parameters in the basket with colon breaking the basket.
      * Bug fix: Surcharges applied twice on frontend.
      * Bug fix: Surcharge with DIRECT not working.
      * Bug fix: IWD support removed as it was causing minor script issues on 3.x version.
      * Bug fix: Surcharge with TOKENS and DIRECT.
      * Bug fix: POST request were not being sent as string.
      * Bug fix: Infinite JS loop when using Sage Pay Suite, One Step Checkout and IE <= 10.
      * Bug fix: CardType field is required error when using default token with DIRECT.

01-Oct-2014
  - Version 3.0.18.1
      * Improvement: New Sage Pay logo and style fixes.
      * Bug fix: Error for GUEST and REGISTER checkout on Magento 1.9+
      * Bug fix: Delivery data error on PayPal when using quick checkout button in cart page.
      * Bug fix: Validation issue with MasterCard Debit.

04-Ago-2014
  - Version 3.0.18
      * New feature: Configuration setting for cart health check.
      * New feature: Transaction detail on row click for DIRECT REFUND grid.
      * New feature: Possibility to delete direct refund from database.
      * New feature: Adding Master Card Debit compatibility to DIRECT integration.
      * New feature: Compatibility with new Recurring module.
      * Improvement: On SERVER frontend transactions sending parameter `Language` based on Magento' locale.
      * Improvement: Showing Bank Auth. Code on payment information in Magento admin order view.
      * Improvement: Updating Sage Pay logo.
      * Improvement: JS issues fixing in checkout.
      * Improvement: Adding `redirect` 3D layout for DIRECT integration.
      * Improvement: Check that amounts match before saving the order in Magento.
      * Improvement: IWD_OnePageCheckout compatibility improvements.
      * Improvement: New credit card icons, retina display compatible.
      * Improvement: Checking that transaction and order amounts match before saving the order.
      * Bug fix: Fixing encode on emails for SERVER backend orders.
      * Bug fix: utm_nooverride in FORM transactions.
      * Bug fix: Fixing MD5 signature issues for SERVER backend orders.
      * Bug fix: Fixing issue when getRemoteAddr() returns more than one IP address.
      * Bug fix: Creating invoices on Multishipping orders.
      * Bug fix: Vendorname not set when creating tokens from Customer Edit in Magento backend.
      * Bug fix: Surcharge fix with taxes issues not being added.
      * Bug fix: Basket for Sage50 issues with numeric SKUs.
      * Bug fix: Emails were not sending sometimes on PayPal orders.
      * Bug fix: On FORM integration, setting `switcher` as Transaction Currency was not being taken into account.

27-Jan-2014
  - Version 3.0.16
      * New feature: Adding Edit option for transactions, with ACL permission.
      * New feature: Payment failed emails when a transaction is rejected, with configuration option.
      * New feature: Discounts on BasketXML.
      * Improvement: Changin tables storage engine from MyISAM to InnoDB.
      * Improvement: Adding new code on fraud grid to make it faster.
      * Improvement: Adding ReD shields on orders grid when available.
      * Improvement: Adding more logging data for FORM transactions.
      * Improvement: Possibility (as config option) to ignore address validation when saving the order in Magento.
      * Bug fix: Problems with surcharges.
      * Bug fix: One Step Checkout, saving feedback entered on checkout step.
      * Bug fix: Server integration callback urls on multistore setup.
      * Bug fix: Saving correctly Magemaven order comments.
      * Bug fix: CV2 value on testing data for AMEX.
      * Bug fix: On DEFERRED transactions, release issues, was sending repeatdeferred.
      * Bug fix: PayPal customer issues on callback when creating a new account.
      * Bug fix: Grand total issue with Server integration and one step checkout modules.
      * Bug fix: Check for correct permission to show orphan notifications bar in admin.

21-Nov-2013
  - Version 3.0.14
      * Improvement: Refactoring CRON to retrieve 3rd man scores.
      * Improvement: Dont update notification feed if Mage_AdminNotification is disabled.
      * Improvement: Adding $_eventPrefix and $_eventObject to Ebizmarts_SagePaySuite_Model_Sagepaysuite_Transaction.
      * Improvement: Different modes for PayPal in frontend, you can use cart button, checkout payment method or both at the same time.
      * Bug fix: Shield renderer for fraud score was showing green for scrore gte 30.
      * Bug fix: Surcharge fixes for all integrations.
      * Bug fix: Token bug fix with session ids starting with numbers.
      * Bug fix: Token registered with different vendor name not available in store.
      * Bug fix: MOTO orders with different currency than base.
      * Bug fix: MOTO orders not sending order email.
      * Bug fix: MOTO virtual orders.
      * Bug fix: Ironing bugs on BasketXML.
      * Bug fix: Correcting typo on Sage Pay Deferred Orders.
      * Bug fix: IE10 strange issue with comment on JS file in frontend.
      * Bug fix: MOTO orders issue with payment methods not being sagepay on some browsers.

08-Oct-2013
  - Version 3.0.10
      * Improvement: Magento 1.8.0.0 CE compatible.
      * Improvement: Add IgnoreModule on config so Mage_Log does not take it into account
      * Improvement: Additional logging on SERVER notification controller
      * Improvement: Default iframe height for SERVER inFrame is now 505px
      * Improvement: Livepipe libraries upgrade
      * Bug fix: Fix error in FORM integration, transaction currency setting was not working.
      * Bug fix: Fix problem on callback when cancelling moto order
      * Bug fix: Cart contents not sent in TOKEN transactions
      * Bug fix: Transaction currency issue fix on refund
      * Bug fix: Fix on sage50 basket
      * Bug fix: Fix empty value on PayPal callback
      * Bug fix: Problem with refund on repeatdeferred
      * Bug fix: Additional logging on SERVER controller and handling INVALID requests properly
      * Bug fix: Fix error in PayPal via Sage Pay transactions when it fails in Magento

29-Jul-2013
  - Version 3.0.8
    * New feature: Adding DEFERRED and AUTHENTICATE payment actions for PayPal transactions.
    * Improvement: Making CRON job for retrieve 3rdman faster.
    * Improvement: Using REPEATDEFERRED when transaction is DEFERRED instead of REPEAT.
    * Improvement: Adding configuration option for CURLOPT_SSL_VERIFYPEER, default is NO.
    * Improvement: DB layout rewrites only for checkout related problems with JS.
    * Bug fix: Reserved order id.
    * Bug fix: Error 4009 (Amount = 0.00) on some situations.
    * Bug fix: Multiple COMPLETE requests on PayPal transactions with some IE versions.
    * Bug fix: MOTO orders issue on EE 1.13.x.
    * Bug fix: Custom fields and loader state fix on MAC checkout with SERVER integration.

04-Jul-2013
  - Version 3.0.7
    * New feature: Adding DIRECTREFUNDs from Magento.
    * Improvement: Set default basket format to Sage50 compatible, not BasketXML.
    * Improvement: Adding help link on Orphans notifications.
    * Improvement: Fixing direct moto html form format.
    * Improvement: Hide Customer Cards tab in customer edit in Admin if token is not enabled.
    * Improvement: CURLOPT_SSL_VERIFYPEER set to TRUE.
    * Improvement: Strip invalid postcode characters before pushing to Sage Pay.
    * Improvement: Magemaven_OrderComment compatibility.
    * Improvement: Adding Sage Pay Dashboard link to Admin panel menu in top level.
    * Bug fix: Double check credentials when checking 3rdman in admin.
    * Bug fix: Sage Pay Token, _setRequestCurrencyAmount method is not defined.
    * Bug fix: Currency code is not valid on MOTO orders.
    * Bug fix: Use AUTHORISE for REPEAT payments when original transaction is AUTHENTICATE.
    * Bug fix: Dont halt invoice if DEFERRED transaction has expired, just show a message.
    * Bug fix: Totals and FaxNo in BasketXML
    * Bug fix: Removing sanitize on shipping method on PaymentController
    * Bug fix: Redirecting back to cart on PayPal failed transactions.

06-Jun-2013
  - Version 3.0.6
    * Improvement: Adding Sage50 basket, you can choose in config wether to use Sage50 or BasketXML
    * Improvement: Magento EE 1.13.0.0 compatibility
    * Bug fix: Available card types from config on Token FORM in MyAccount
    * Bug fix: BasketXML bug fixes
    * Bug fix: Fix on Surcharges
    * Bug fix: Fixing REPEAT issue with different currencies
    * Bug fix: Row deletion in token card list in checkout


21-May-2013
  - Version 3.0.5.1
    * Bug fix: MOTO currency on multi store setup
    * Bug fix: Disable sending basket contents for all integrations until we fix the BasketXML issue
    * Bug fix: Token form problem when cancelling token CV2 page
    * Bug fix: substr issue on recipientAdd2 in BasketXML

29-Apr-2013
  - Version 3.0.5
    * Improvement: Adding eMailMessage parameter to FORM request.
    * Improvement: Compatibility [BETA] with IWD_OnePageCheckout
    * Improvement: Adding support form to system config section.
    * Improvement: Adding IP details to detail data when retrieving using Admin & Reporting API.
    * Bug fix: Error in Dashboard with settlements.
    * Bug fix: Error in Deferred orders grid when using filters.
    * Bug fix: Encoding strings when creating XML structs to send to Sage Pay
    * Bug fix: Surcharge undefined index error.
    * Bug fix: Token issues with 0 customer_id in db
    * Bug fix: Adding basket error code to retry routine.
    * Bug fix: Send 2.23 as protocol if MODE is SIMULATOR
    * Bug fix: Address information data for virtual orders.
    * Bug fix: RELEASE amount problem when order is in different currency than base.
    * Bug fix: BasketXML errors.
    * Bug fix: Postcode not provided fix, we send 000 if not provided.
    * Bug fix: Surcharge error breaks Sweet Tooth module for example.
    * Bug fix: Adding try catch block on FORM callback.
    * Bug fix: Checking ACL to show Orphans notification.

19-Mar-2013
  - Version 3.0.4
    * Improvement: Use of instanceof instead of get_class to add STATE column on Sales > Orders grid
    * Improvement: Saving TRNAMOUNT on sagepaysuite_transaction table
    * Improvement: Adding new feature to fetch transaction data from CRON Async.
    * Improvement: Change PROFILER logs prefixes to use VendorTxCode if possible.
    * Bug fix: Change CURLOPT_SSL_VERIFYHOST to 2 on Sage Pay Reportin API calls.
    * Bug fix: Dashboard reports for 30 days timeframe.
    * Bug fix: Re send request if error received is 3195 or 3021, basket recoverable related errors
    * Bug fix: Multishipping address amount for SERVER frontend orders.
    * Bug fix: Order state for initial status, for example for AUTHENTICATE orders.
    * Bug fix: Fix for FORM integration on some situations Amount sent was 0.00
    * Bug fix: Giftmessages on SERVER MOTO integration
    * Bug fix: Discount problems with SERVER MOTO integration
    * Bug fix: Surcharge amount on PDFs.
    * Bug fix: OneStepCheckout feature implemented. Place order on the emails account without the password
    * Bug fix: OneStepCheckout token stuck.

06-Mar-2013
  - Version 3.0.3
    * Bug fix: Installation problem fix, error was: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'sagepayreporting_fraud' doesn't exist

04-Mar-2013
  - Version 3.0.2
    * New feature: Notifications from Ebizmarts site.
    * New feature: Adding AUTHENTICATE to automatic capture feature.
    * New feature: Adding data to Dashboard, Settlements.
    * New feature: Checking Web config for safe transactions and adding notice on config section if not okay.
    * New feature: Check stock before pushing transaction to Sage Pay. Redirect to cart if not okay.
    * Improvement: Change grid render names to magento like class path.
    * Improvement: Change shields score for Orders grid. Some of them are confussing.
    * Improvement: Adding created_at for all tables and adding new columns to sagepaysuite_action table.
    * Improvement: Change color for NOTCHECKED on fraud grid.
    * Improvement: Data validation for new BasketXML field.
    * Improvement: Adding additional data for AUTHORISE payments on admin.
    * Bug fix: Promo on admin orders.
    * Bug fix: Token not created on SERVER integration with Multiple Address Checkout.
    * Bug fix: Currency and amount problem on automatic authorise with different currency than base.
    * Bug fix: Fix for notice on CURLOPT_SSL_VERIFYHOST
    * Bug fix: Adding basket for FORM post data.
    * Bug fix: Token bug on OSC.

13-Feb-2013
   - Version 3.0.0
	   * New feature: Dashboard with graphs showing last transactions within 1 to 30 days.
       * New feature: Removal of token feature for GUEST checkout.
       * New feature: MOTO configuration for Admin & Access API.
       * New feature: Added store switcher for Admin & Reporting API operations. Allowing to use credentials for API per store.
       * New feature: Added most error messages to translation files
       * New feature: Added transalation files for Deutsch and Spanish.
       * New feature: Adding renderer for order increment id on grids.
       * New feature: Adding renderer for customer name on grids.
       * New feature: Adding renderer for tx state id (system status) on grids.
       * New feature: Adding surcharges configuration from Magento.
       * New feature: Added System status to Orphans grid.
       * New feature: Added full redirection to Sage Pay for SERVER integration (frontend) specially for European Payments.
       * New feature: Changed Sage Pay protocol to new version 3.00
       * New feature: Implemented BasketXML. A more flexible version of the current basket field which can be used instead of the basket field.
       * New feature: Implemented CustomerXML. This can be used to supply information on the customer for purposes such as fraud screening.
       * New feature: Implemented SurchargeXML. Use this field to override current surcharge settings in "My Sage Pay" for the current transaction. Percentage and fixed amount surcharges can be set for different payment types.
       * New feature: Tokens are now created off transaction not before posting transaction.
       * New feature: Adding ReD response to transaction.
       * Improvement: Token cards are registered alongside transaction now.
       * Improvement: Tokens available for select when creating order with SERVER MOTO.
       * Improvement: Checking for valid shipping method when posting order.
       * Improvement: Implemented new, more secure encryption of data for FORM integration.
       * Improvement: Adding ContactNumber to SERVER post data.
       * Improvement: Adding CardHolder name to transaction data when sync from API.
       * Improvement: Improvements for better mobile experience.
       * Bug fix: Invoice not being voided when amount is equal to Grand Total.
       * Bug fix: Added several translation strings to CSV translation files.
       * Bug fix: Fixed amount on invoices when currency is different to base.
       * Bug fix: Fixed error when refunding whole transaction, void was not being processed due to calculation issue.
       * Bug fix: Invoice issues with Multiple Address Checkout.
       * Bug fix: Token issues, currency and txtype not being saved on token transactions.
       * Bug fix: Changing SWITCH (Maestro UK) card logo to Maestro International logo.

30-Nov-2012
 - Version 1.1.19.2
	* Improvement: Using Magento's core roundPrice function to round amounts instead of number_format.
		* Improvement: Adding customer email to contact number in CustomerContactInfo on local storage.
		* Improvement: Setting autocomplete="off" on cc forms.
		* Bug fix: Undefined object on SagePayReporting cron.
		* Bug fix: Token bugs on transactions without payment details, wrong token selected when using OSC.
		* Bug fix: Refund issue for transactions not placed in base currency.
		* Bug fix: Multiple refunds on authenticate transactions, it was always taking into account latest transaction.
		* Bug fix: Amount missmatch when using SERVER integration.
		* Bug fix: Restoring OSC loading object when 3D secure window is closed.

17-Oct-2012
	- Version 1.1.19.1
		* New feature: Dispatching 2 events, one for config data loading and another one when a transaction is saved in Magento.
		* Bug fix: Typo on word "ocurred".
		* Bug fix: The module does not use DateTime::diff anymore to avoid servers without PHP 5.3
		* Bug fix: 3D data saved to database, not session.
		* Bug fix: Changing the way we call the cache store, it was not a good approach.
		* Bug fix: Removing basket sending on PayPal transactions to avoid issues.
		* Bug fix: Fixing Store Currency option for SERVER integration, it was not working at all.
		* Bug fix: Deleting a token card from local storage if SagePay returns error 4057 when trying to delete.

17-Sep-2012
	- Version 1.1.19
		* New feature: SAGE50 support for basket lines.
		* New feature: Api synchronization, see http://wiki.ebizmarts.com/sage-pay-configuration-settings/reporting-api
		* Bug fix: When compilation is on, other payment methods fail.
		* Bug fix: MOTO vendorname not taken into account.
		* Bug fix: Orders partially released were voided if canceled.
		* Bug fix: Adding ApplyAVSCV2 on TOKEN transactions.
		* Bug fix: FORM integration store scope fields were not visible.

24-Ago-2012
	- Version 1.1.17
		* New Feature: Order editing support.
		* New Feature: Mass delete for token cards on admin panel
		* New Feature: Possibility to switch between LOW and NORMAL template profiles in SERVER and SERVER MOTO integrations.
		* New Feature: Extending Magento core API (SOAP/XMLRPC), available calls are: sage_pay_transaction.list, sage_pay_transaction.info (by vpstxid), sage_pay_reporting.fraud_detail, sage_pay_reporting.info (information directly from Sage Pay Reporting API)
		* Adding orphans on various payment stages so the administrator is notified for example if card was not authed.
		* Sanitize post data to avoid xss attacks
		* Adding Android and BlackBerry user-agent to detection to fix modal box issue
		* Fixing bug on PayPal description, empty on some ocassions.
		* Fixed bug on automatic thirdman score updates for old orders.
		* Fixed performance issues on backend and database
		* Fixing bug with REGISTER and GUEST token cards on some scenarios.
		* Fixed not sent email when using DIRECT and 3D.
		* Fixed bug on SERVER MOTO related to Original Price being 0.00

23-Jul-2012
	- Version 1.1.15
		* Fixing redirection issue in SERVER integration
		* Adding Currency Switcher support for Transaction Currency
		* Fixing EUR symbol in cart
		* Removing condition on email sending on DIRECT protocol, causes issues when reverse proxies are in place
		* OneStepCheckout GiftMessage adding
		* Shorting MOTO orders urls for SERVER inframe protocol
		* Fix issue when REPEATing FORM transaction
		* Fixing price on basket
		* Fixing issue where REPEAT urls where not reacheable when repeating a FORM transaction

13-Jun-2012
	- Version 1.1.13
		* Adding PayPal description
		* Adding support link to config

07-May-2012
	- Version 1.1.11
		* Fixed bug with YEN currency
		* Fix bug on validateQuote for MultiShipping
		* Compatibility with Magento EE 1.12 and CE 1.7.0.0

16-April-2012
	- Version 1.1.9
		* Fixed bug when saving Edit Order with any payment method
        * Fixing bug with FORM protocol on additional operations such as abort
        * Fixing but in token transactions for MOTO orders
        * Validating quote before sending transaction to Sage Pay
        * Fixing doulbe email failed payments on SERVER integration
        * Adding 'order_status' config setting for PayPal integration
        * Fixing FORM bug when using OSC and Sage Pay returns an error

24-Feb-2012
	- Version 1.1.7
		* Payment System details on Transaction Details (API)
		* Fixing error on SERVER integration (cant delete token card)
		* Fixing typo "orfans"
		* Add PaymentSystemDetails and ECI value to transaction data (From API)
		* Fixing OSC compatibility issues with Shipping Address
		* Adding square brackets on SKU items on Basket
		* Fixing REFUND error when refunding order TOTAL
		* Change deletquote to set it as notactive instead of deleting


04-Jan-2011
	- Version 1.1.5
		* Fix token issue with CV2 when default token is loaded (happens because of radiobutton enabling payment method JS stuff)
		* Fix for error 3021 on Basket for Bundle items
		* Fix amount sent on Multiple address checkout with DIRECT integration
		* Added product options to Sage Pay Basket field
		* Reset OSC button and loading spinner for SERVER integration when transaction fails for whatever reason
		* Validate quote object before sending transaction to Sage Pay, mostly to fix OSC related issues
		* Fixed OrderCurrency code on Basket field when in STORE scope
		* Check for duplicates before reserving order id
		* Perform a VOID operation if refunding full order amount
		* Fix issue when REPEAT payments fail that creates order with no payment details
		* Added Deferred-Capture option, automatically RELEASES a DEFERRED transaction when reached the SUCCESS page
		* Do not import PAYPAL callback shipping details if AddressStatus is NONE to prevent errors when completing transaction
		* Adding possibility to Invoice FORM integration orders to RELEASE or AUTHORISE transactions from Magento backend
		* Fixing data issues not saved when registering Token Card
		* Show correct currency code on Refunds grid

11-Nov-2011
	- Version 1.1.3
		* Fix OSC issue when logged in customer checksout with new address
		* Showing PayPal additional information on order view page ADMIN
		* PayPal method title on checkout radiobutton title fix
		* Fix TOKEN issue on Magento 1.5+
		* PayPal express fix for AddressStatus = NONE
		* Check for existing email on MOTO orders SERVER
		* Fixing PostCode for Ireland addresses on DIRECT integration
		* Token cards in customer edit tab and availability to add token from admin panel
		* Truncate basket field to max allowed by sagepay
		* Fixed JS error on iphone/ipod touch with lightbox
		* OSC disabled button fix, need to edit OSC template to change variable scope
		* REPEAT payments for ADMIN
		* Requests PROFILER

06-Oct-2011
	- Version 1.1.1
		* Fix thirdman id error on backend.
		* Fix secure URL on frontend
		* Invoice order email config option
		* Adding vendor_email to FORM integration

03-Oct-2011
	- Version 1.1.0
		* Fix backend create order form validation
		* Adding DELETE options on transactions listed under Sales - SagePay - Payment Transactions
		* Magento EE 1.11 compatibility
		* Send invoice email when creating it automatically (PAYMENT)
		* Fix virtual order FORM integration
		* Adding 3D Secure column to fraud information grid
		* Fixing data issues on "Sage Pay Fraud Information"
		* Adding "sort order" to PayPal integration
		* Option to save token card or not

24-Aug-2011
	- Version 1.0.37
		* Fix REGISTER TOKEN currency in checkout
		* Fix IE bug on window.js
		* Adding required-entry class for cv2 and cc number for direct form (OSC issue)
		* Fix DIRECT 3D payment not creating invoice if on PAYMENT mode
		* Fixed TOKEN issue with CV2 when registering token
		* Magento CE 1.6 compatibility
		* Fix for PayPal and FORM on OSC

02-Aug-2011
	- Version 1.0.36
		* Fix street2 import on PayPal payments
		* SERVER protocol RemoteIpFix
		* FORM fix on OSC
		* Fixing overflow auto on IE8 3D lightbox
		* Fix INVOICE order on Downloadable cart
		* FORM adding SendEMail flag
		* Fixing BASKET display on transaction detail
		* Correct bug on SERVER protocol DeliveryState when cart is VIRTUAL

12-Jul-2011
	- Version 1.0.35
		* PayPal email title correction
		* Fix admin log viewer error if no SagePaySuite log dir exist
		* Fix token card delete error when on HTTPS
		* Fix MOTO multiple vendors on multiple websites error
		* PayPal register customer implementation
		* Hide issue number and start date upon form show if no card is selected
		* Fix trhirdman id error on order view

24-Jun-2011
	- Version 1.0.34
		* Correcting typos on system.xml
		* PayPal import ISO-8859-15 encoded chars fix
		* SagePayReporting 3rdman CRON fix
		* SERVER when cancelling order if message is blank, don't show alert box
		* Fix MAC bug when no token is enabled
		* PayPal fix redirection urls bug
		* PayPal title fix show correct method title on emails, backend, etc.
		* Added 3rdman breakdown

07-Jun-2011
	- Version 1.0.33
		* Adding check to not break API calls on sagepay orders not placed with suite.
		* Fix MOTO SERVER long url
		* Added checking for Idev_OneStepCheckout module when importing OSC information to quote

30-May-2011
	- Version 1.0.32
		* PayPal bugfix for currency on completing order.
		* Changing orphans row click to view sagepay detail.
		* PayPal secure url fix on review post.
		* PayPal don't check for shipping estimate on checkout button

26-May-2011
	- Version 1.0.31
		* PayPal bugfix for incheckout, billing address was overwritten.

24-May-2011
	- Version 1.0.30
		* PayPal incheckout sending customer billing and shipping address
		* Bug fix on authorises request, was not saving amount
		* Bug fix on get transaction details for related transactions such as AUTHORISES
		* Adding icons for NOTMATCHED, OK, NOTCHECKED, etc. flags

16-May-2011
	- Version 1.0.29
		* Reward points for Enterprise edition, SERVER integration bug fix.
		* Bug fix on PaymentController when compilation enabled.
		* Bug fix on 3D secure lightbox on IE7 and below.

09-May-2011
    - Version 1.0.28
        * Added specific payment information template for PDF printing in admin panel.
