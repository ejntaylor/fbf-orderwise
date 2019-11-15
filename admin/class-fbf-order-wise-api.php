<?php


class Fbf_Order_Wise_Api
{

    private $version;
    private $plugin;



    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('parse_request', array($this, 'endpoint'), 0);
        add_action('init', array($this, 'add_endpoint'));
    }

    public function enqueue_styles()
    { }

    public function enqueue_scripts()
    { }

    // /api/v2/orderwise_export
    // /api/v2/orderwise_success

    public function endpoint()
    {
        global $wp;

        $endpoint_vars = $wp->query_vars;

        // if endpoint
        if ($wp->request == 'api/v2/orderwise_export') {

            // Your own function to process end pint
            $this->processEndPointXML($_REQUEST);

            exit;
        } elseif ($wp->request == 'api/v2/orderwise_success') {

            // Your own function to process end pint
            $this->processEndPointResponse($_REQUEST);

            exit;
        }
    }


    public function add_endpoint()
    {

        add_rewrite_endpoint('orderwise', EP_PERMALINK | EP_PAGES, true);
    }

    public function processEndPointXML($request)
    {

        // auth checks

        // get the latest export id
        $export_id = $this->getLatestExportId();


        if(!$export_id) {
            echo 'No Exports Found';
            exit;
        }


        // get export
        $export = wc_customer_order_csv_export_get_export(wc_clean($export_id));

        // check export
        if (!$export) {
            echo 'Error: Export not found';
        }

        $output_type = $export->get_output_type();

        $filename = $export->get_filename();

        // we are intentionally using text/xml here to prevent a console warning
        $content_type = 'text/xml';

        header('Content-type: ' . $content_type);
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        $file_size = $export->get_file_size();

        if ($file_size && 0 < $file_size) {
            header('Content-Length: ' . $file_size);
        }

        $output_resource = fopen('php://output', 'w');

        $export->stream_output_to_resource($output_resource);

        fclose($output_resource);
    }

    public function processEndPointResponse()
    {

        try {

            // dev
            // $post_received = '12345~17~19';

            // receive POST var of tilda separated order numbers
            $post_received = $_POST['order_ids'];

            // create array from POST
            $result = [];
            foreach (explode('~', $post_received) as $order_id) {


                // exit if not
                if (!$order_id) {
                    return;
                }

                $order = wc_get_order($order_id);

                // mark the received order numbers as status == processing
                if ($order) {
                    $order->update_status('completed');
                    $completed[] = $order_id;
                } else {
                    $failedl[] = $order_id;
                }
            }

            // var_dump($completed);

        } catch (exception $e) {
            //code to handle the exception
            var_dump($e);
        }
    }

    // find last entry which starts with wc_customer_order_export_background_export_job_

    public function getLatestExportId()
    {

        // for dev
        // $export_id = 'd8be76d4479079623e23bc32b8235aca';

        // call wp database and get last added export_job
        global $wpdb;
        $query = "SELECT * FROM wp_options WHERE option_name LIKE 'wc_customer_order_export_background_export_job_%' ORDER BY option_id DESC LIMIT 1";
        $result = $wpdb->get_row($query);

        // empty message
        if(!$result) {
            echo 'No Exports Found';
            exit;
        }

        // get option name and strip to get id
        $option_name = $result->option_name;
        $export_id = preg_replace('/^wc_customer_order_export_background_export_job_/', '', $option_name);

        // return
        return $export_id;
    }
}
