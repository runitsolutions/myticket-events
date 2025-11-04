<?php defined( 'ABSPATH' ) or exit;

// load mpdf and WooCommerce variables
require_once MYTICKET_PATH . 'inc/mpdf/vendor/mpdf/mpdf/mpdf.php';

$mpdf                           = new mPDF(['debug' => true]);
$order                          = new WC_Order( $order_id );
$uploads                        = wp_get_upload_dir();
$ticketDir                      = $uploads['basedir']."/tickets";
$formatted_shipping_address     = $order->get_formatted_shipping_address();
$formatted_billing_address      = $order->get_formatted_billing_address();
$line_items                     = $order->get_items( 'line_item' );
$name                           = wc_get_order_item_meta($order_item_id, "name");
$file                           = sanitize_file_name($ticketDir."/".$order_id."_".$order_item_id.".pdf");
$product                        = new WC_Order_Item_Product($order_item_id);
$item_id                        = $order_item_id;


// optional use
// $order_item                     = new WC_Order_Item_Product($order_item_id);
// $product                        = wc_get_product( $order_item->get_product_id() );
// $short_description              = $product->get_short_description();

// make sure that ticket directory exists
wp_mkdir_p($ticketDir);

// Ajax urls 
$ajaxurl = '';
if( in_array('sitepress-multilingual-cms/sitepress.php', get_option('active_plugins')) ){
    $ajaxurl .= admin_url( 'admin-ajax.php?lang=' . ICL_LANGUAGE_CODE );
} else {
    $ajaxurl .= admin_url( 'admin-ajax.php');
}


$header = '<!--mpdf
    <htmlpageheader name="header">
        <table width="100%" style="font-family: sans-serif;padding-left:20px;padding-top:0px;">
            <tr>
                <td width="20%" style="color:#111111;padding-left:60px;text-align:left; vertical-align: middle;">
                    <barcode code="'."myticket:".esc_attr($order_id).",".esc_url($ajaxurl).",".esc_attr($order_item_id).",0,0".'" size="0.9" type="QR" error="M" disableborder="1" class="barcode" />
                    <br/>
                    <br/>
                    <br/>
                    <span style="width:50px;font-weight:bold;font-size:20pt;text-align:center;display:none;">'.str_replace( ' ', '<br/>',  esc_html($name) ).'</span><br />
                    <br/>
                </td>
                <td width="60%" style="color:#111111;padding-left:45px;font-weight:bold;vertical-align: top;">
                    <p>Row ' . esc_html( wc_get_order_item_meta( $item_id, "row") ) . '</p>
                    <p>Seat ' . esc_html( wc_get_order_item_meta( $item_id, "seat") ) . '</p>
                    <br/>
                    <br/>
                    <p>' . esc_html( $product->get_name() ) . '</p>
                    <p>' . wc_get_order_item_meta( $item_id, "title") . '</p>
                    <p>' . wc_get_order_item_meta( $item_id, "date") . ' at ' . wc_get_order_item_meta( $item_id, "time") . '</p>
                    <br/>
                    <br/>
                    <br/>
                    <div>
                        <p>' . wc_get_order_item_meta( $item_id, "venue") . '</p>
                        <p style="font-size:0.8rem;">Thomas A. Robinson National Stadium</p>
                        <p style="font-size:0.8rem;">Nassau, BAHAMAS SP 64113</p>
                    </div>

                </td>
                <td width="20%" style="text-align: right; vertical-align: middle;padding-right:50px;">
                    <table border="0">
                        <tr>
                            <th style="text-rotate: 90;">Row ' . esc_html( wc_get_order_item_meta( $item_id, "row") ) . ' Seat ' . esc_html( wc_get_order_item_meta( $item_id, "seat") ) . '</th>
                            <th style="text-rotate: 90;">' . esc_html( $product->get_name() ) . '</th>
                            <th style="text-rotate: 90;font-size:0.8rem;">' . wc_get_order_item_meta( $item_id, "date") . ' at ' . wc_get_order_item_meta( $item_id, "time") . '</th>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </htmlpageheader>
    
mpdf-->

<style>

    @page { sheet-size: 210mm 74mm; }

    @page {
        margin-top: 0cm;
        margin-bottom: 0cm;
        margin-left: 0cm;
        margin-right: 0cm;
        footer: html_letterfooter2;
        background-color: pink;
        background-image: url("' . plugins_url( 'background-ticket.jpeg', __FILE__ ). '");
        background-repeat: no-repeat;
        background-size: cover; 
    }
  
    @page :first {
        margin-top: 8cm;
        margin-bottom: 4cm;
        header: html_header;
        footer: _blank;
        resetpagenum: 1;
        background-color: lightblue;
    }

</style>';

$mpdf->img_dpi = 150;
$mpdf->WriteHTML($header);

// print to file and return its path
if ($to_file){
    
    $mpdf->Output($file,'F');
    return $file;
    
// print to browser
}else{
    $mpdf->Output();
}

?>