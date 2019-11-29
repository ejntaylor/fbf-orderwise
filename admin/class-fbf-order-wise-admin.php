<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.chapteragency.com
 * @since      1.0.0
 *
 * @package    Fbf_Order_Wise
 * @subpackage Fbf_Order_Wise/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Fbf_Order_Wise
 * @subpackage Fbf_Order_Wise/admin
 * @author     Kevin Price-Ward <kevin.price-ward@chapteragency.com>
 */
class Fbf_Order_Wise_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_filter('wc_customer_order_xml_export_suite_format_definition', array($this, 'sv_wc_xml_export_custom_format_settings'), 10, 3);
        add_filter('wc_customer_order_xml_export_suite_orders_header', array($this, 'sv_wc_customer_order_xml_export_suite_orders_xml_data_add_attributes_to_root_element'));
        add_filter('wc_customer_order_xml_export_suite_orders_footer', array($this, 'sv_wc_customer_order_xml_export_suite_orders_xml_data_add_attributes_to_root_element_footer'));
        add_filter('wc_customer_order_xml_export_suite_orders_xml_data', array($this, 'sv_wc_xml_export_order_name'), 10, 3);
        add_filter('wc_customer_order_xml_export_suite_order_data', array($this, 'sv_wc_xml_export_order_item_format'), 10, 3);
        add_filter('wc_customer_order_xml_export_suite_order_line_item', array($this, 'sv_wc_xml_export_order_line_item'), 10, 3);
        // add_filter('wc_customer_order_xml_export_suite_orders_xml', array($this, 'sv_wc_xml_export_output'), 10, 3);
        add_filter('wc_customer_order_xml_export_suite_order_export_format', array($this, 'sv_wc_xml_export_order_format'), 10, 3);
        add_filter('wc_customer_order_xml_export_suite_order_line_item', array($this, 'sv_wc_xml_export_line_item_addons'), 10, 3);
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Fbf_Order_Wise_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Fbf_Order_Wise_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        //wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/fbf-order-wise-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Fbf_Order_Wise_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Fbf_Order_Wise_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        //wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/fbf-order-wise-admin.js', array('jquery'), $this->version, false);
    }



    public function sv_wc_xml_export_custom_format_settings($definition, $export_type, $format)
    {

        // could also check $export_type for 'orders' or 'customers'
        if ('custom' === $format) {
            $definition['xml_encoding'] = 'UTF-8';
        }

        return $definition;
    }

    function sv_wc_customer_order_xml_export_suite_orders_xml_data_add_attributes_to_root_element($header)
    {
        return str_replace(
            '<Orders>',
            '<XMLFile xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
 <SalesOrders>',
            $header
        );
    }


    function sv_wc_customer_order_xml_export_suite_orders_xml_data_add_attributes_to_root_element_footer($footer)
    {
        return str_replace(
            '</Orders>',
            '</SalesOrders></XMLFile>',
            $footer
        );
    }

    public function sv_wc_xml_export_order_name($orders_format, $orders)
    {
        $orders_format = array(
            'SalesOrder' => $orders,
        );

        return $orders_format;
    }

    function sv_wc_xml_export_order_item_format($order_data, $order)
    {

        // require_once(ABSPATH . '/wp-content/plugins/woocommerce-customer-order-xml-export-suite/includes/class-wc-customer-order-xml-export-suite-generator.php');

        // format date
        $datetime = new DateTime($order->order_date);
        $date = $datetime->format("Y-m-d\TH:i:s");


        // create XML feed
        $new_format = [
            // 'OrderNumber' => get_post_meta($order->id, '_order_number', true),
            'OrderNumber' => $order->get_id(),
            'OrderDate' => $date,
            'OrderAnalysis' => 'ECommerce Site',
            'SpecialDeliveryInstructions' => $order->customer_note,
            'CustomerOrderRef' => $order->get_id(),
            'DeliveryMethod' => $order->get_shipping_method(),
            'DeliveryGross' => $order->get_total_shipping(),
            'DeliveryNet' => $order->get_total_shipping() - $order->get_shipping_tax(),
            'DeliveryTax' => $order->get_shipping_tax(),
            'DeliveryTaxCode' => '',
            'OrderGross' => $order->get_total(),
            'OrderNet' => $order->get_total() - $order->get_total_tax(),
            'OrderTax' => $order->get_total_tax(),
            'AmountPaid' => $order->get_total(),
            'Customer' => [
                'eCommerceAccountNumber' => '',
                'StatementName' => $order->get_formatted_billing_full_name(),
                'StatementAddress1' => $order->billing_address_1,
                'StatementAddress1' => $order->billing_address_2,
                'StatementTown' => $order->billing_city,
                'StatementCounty' => $order->billing_state,
                'StatementCountry' => $order->billing_country,
                'StatementCountryCode' => $order->billing_country,
                'StatementPostcode' => $order->billing_postcode,
                'StatementEmail' => $order->billing_email,
                'StatementTelephone' => '',
                'InvoiceName' => $order->get_formatted_billing_full_name(),
                'InvoiceAddress1' => $order->billing_address_1,
                'InvoiceAddress2' => $order->billing_address_2,
                'InvoiceTown' => $order->billing_city,
                'InvoiceCounty' => $order->billing_state,
                'InvoiceCountry' => $order->billing_country,
                'InvoiceCountryCode' => $order->billing_country,
                'InvoicePostcode' => $order->billing_postcode,
                'InvoiceEmail' => $order->billing_email,
                'InvoiceTelephone' => '$order->billing_phone',
                'CustomerContact' => [
                    'Name' => $order->get_formatted_billing_full_name(),
                    'Email' => $order->billing_email,
                    'Position' => '',
                    'Telephone' => '$order->billing_phone',
                    'Fax' => '',
                    'Email' => $order->billing_email,
                    'Mobile' => '',
                    'Extension' => '',
                    'Salutation' => ''
                ],
                'DeliveryAddress' => [
                    'Name' => $order->get_formatted_shipping_full_name(),
                    'Contact' => '',
                    'Address1' => $order->shipping_address_1,
                    'Address2' => $order->shipping_address_2,
                    'Town' => $order->shipping_address_city,
                    'County' => $order->shipping_address_state,
                    'Country' => $order->shipping_address_country,
                    'CountryCode' => $order->shipping_address_country,
                    'Postcode' => $order->shipping_address_postcode,
                    'Email' => $order->billing_email,
                    'Telephone' => ''
                ]
            ]
        ];

        $new_format['Payments'] = [
            'SalesPayment' => [
                'Description' => '',
                'Amount' => $order->get_total()
            ]
        ];


        // Line Items

        foreach ($order->get_items() as $item_id => $item_data) {
            $product = $order->get_product_from_item($item_data);

            // skip loop if not product found
            if (!$product) {
                continue;
            }

            // create array
            $items['SalesOrderLine'][] = [
                'eCommerceCode' => $product->get_sku(),
                'Code' => $product->get_sku(),
                'Quantity' => $item_data['qty'],
                'eCommerceItemID' => $product->id,
                'ItemGross' => round($product->get_price(), 2),
                'TaxCode' => $product->tax_class
            ];
        }

        $new_format['Lines'] = $items;


        return $new_format;
    }

    function sv_wc_xml_export_order_line_item($item_format, $order, $item)
    {

        $product = is_callable(array($item, 'get_product')) ? $item->get_product() : $order->get_product_from_item($item);

        // bail if this line item isn't a product
        if (!($product && $product->exists())) {
            return $item_format;
        }

        $arr = array(
            'eCommerceCode'     => $product->get_sku(),
            'Code'               => $product->get_sku(),
            'Quantity'          => $item['qty'],
            'eCommerceItemID'      => $product->id,
            'ItemGross'            => $product->get_price(),
            'TaxCode'             => $product->tax_class
        );

        return $arr;
    }

    // public function sv_wc_xml_export_output($generated_xml, $xml_array, $orders, $ids, $export_format)
    // {

    // 	return $generated_xml;
    // }

    // function sv_wc_xml_export_order_format($orders_format, $orders)
    // {
    // 	$orders_format = array(
    // 		'OrderList' => array(
    // 			'@attributes' => array(
    // 				'StoreName' => get_home_url(),
    // 			),
    // 			'Order' => $orders,
    // 		),
    // 	);
    // 	return $orders_format;
    // }



    function sv_wc_xml_export_line_item_addons($item_format, $order, $item)
    {
        $product = is_callable(array($item, 'get_product')) ? $item->get_product() : $order->get_product_from_item($item);
        // bail if this line item isn't a product
        // if (!($product && $product->exists())) {
        // 	return $item_format;
        // }
        // $addons = [];
        // // get the possible add-ons for this line item to check if they're in the order
        // if (is_callable('WC_Product_Addons_Helper::get_product_addons')) {
        // 	$addons = WC_Product_Addons_Helper::get_product_addons($product->get_id());
        // } elseif (is_callable('get_product_addons')) {
        // 	$addons = get_product_addons($product->get_id());
        // }
        // $product_addons = sv_wc_xml_export_get_line_item_addons($item, $addons);
        // if (!empty($product_addons)) {
        // 	$item_format['AddOn'] = $product_addons;
        // }

        $item_format = [];
    }
}


 // rename OrderLineItem  > SalesOrderLine>
