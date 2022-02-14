<?php

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("Location: ../");
exit;

$params = [];

$dom = new DOMDocument();
$dom->encoding = 'UTF-8';
$dom->formatOutput = true;
$items = $dom->createElement('items');
$dom->appendChild($items);

foreach ($ordered_items as $key => $ordered_item) {
    $item = $dom->createElement('item');

    $purchaseid = $dom->createElement('purchaseid', $order_ext['mp_order_id']);
    $itemid = $dom->createElement('itemid', $ordered_item['itemid']);
    $transporter_name = $dom->createElement(
        'transporter',
        in_array($pm_carriers[$order_ext['shipping_type']], array('Normal', 'Retrait chez le vendeur')) ?
            null : $pm_carriers[$order_ext['shipping_type']]
    );
    $tracking_number = $dom->createElement(
        'trackingnumber',
        $trackingNumber
    );
    $tracking_url = $dom->createElement(
        'trackingurl',
        in_array($pm_carriers[$order_ext['shipping_type']], array('Autre', 'Kiala')) ?
            $trackingUrl : null
    );

    $item->appendChild($purchaseid);
    $item->appendChild($itemid);
    $item->appendChild($transporter_name);
    $item->appendChild($tracking_number);
    $item->appendChild($tracking_url);

    $items->appendChild($item);
}

$filename = sys_get_temp_dir().'/export/trackingpackageinfos.xml';
$dom->save($filename);

$params['file'] = '@'.$filename;

// POST https://ws.fr.shopping.rakuten.com/sales_ws?action=importitemshippingstatus&login=xxxxxx&pwd=xxxxxx&version=2016-05-09
// {
//    body: $params
// }
