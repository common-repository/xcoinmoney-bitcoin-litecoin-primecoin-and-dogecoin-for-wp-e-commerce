<?php
/*
Plugin Name: xCoinMoney bitcoin, litecoin, primecoin and dogecoin for WP e-Commerce
Plugin URI: http://wordpress.org/plugins/xcoinmoney-bitcoin-litecoin-primecoin-and-dogecoin-for-wp-e-commerce/
Description:  Bitcoin, Litecoin, Primecoin and Dogecoin Payments with WP e-Commerce plugin for WordPress.
Version: 1.0
Author: xcoinmoney
*/

$nzshpcrt_gateways[$num]['name'] = 'xCoinMoney';
$nzshpcrt_gateways[$num]['display_name'] = 'xCoinMoney Bitcoins/Litecoins/Primecoins/Dogecoins payments';
$nzshpcrt_gateways[$num]['internalname'] = 'Coinmoney';
$nzshpcrt_gateways[$num]['function'] = 'gateway_coinmoney';
$nzshpcrt_gateways[$num]['form'] = 'form_coinmoney';
$nzshpcrt_gateways[$num]['submit_function'] = "submit_coinmoney";

function form_coinmoney()
{
  $rows = array();

  if (get_option('coinmoney_api_url')) {
    $coinmoney_api_url = get_option('coinmoney_api_url');
  } else {
    $coinmoney_api_url = 'https://www.xcoinmoney.com/api';
  }

  $rows[] = array('xCoinMoney Api URL', '<input name="coinmoney_api_url" type="text" value="' . $coinmoney_api_url. '" />');
  $rows[] = array('Your callback URL', get_option('checkout_url'));
  $rows[] = array('API key', '<input name="coinmoney_apikey" type="text" value="' . get_option('coinmoney_apikey') . '" />');
  $rows[] = array('Merchant ID', '<input name="coinmoney_merchant_id" type="text" value="' . get_option('coinmoney_merchant_id') . '" />');

  $currencies = array("DXX", "BTC", "LTC", "XPM", "DOGE");
  $currenciesSelect = '<select name="coinmoney_currency">';
  foreach ($currencies as $currency) {
    if (get_option('coinmoney_currency') == $currency) {
      $currenciesSelect .= '<option value="' . $currency . '" selected="selected">' . $currency . '</option>';
    } else {
      $currenciesSelect .= '<option value="' . $currency . '">' . $currency . '</option>';
    }
  }
  $currenciesSelect .= '</select>';

  $rows[] = array('Currency', $currenciesSelect);

  if (get_option('coinmoney_dxx_account')) {
    $rows[] = array('DXX account', '<input name="coinmoney_dxx_account" type="checkbox" checked="checked" />');
  } else {
    $rows[] = array('DXX account', '<input name="coinmoney_dxx_account" type="checkbox" />');
  }
  if (get_option('coinmoney_btc_account')) {
    $rows[] = array('BTC account', '<input name="coinmoney_btc_account" type="checkbox" checked="checked" />');
  } else {
    $rows[] = array('BTC account', '<input name="coinmoney_btc_account" type="checkbox" />');
  }
  if (get_option('coinmoney_ltc_account')) {
    $rows[] = array('LTC account', '<input name="coinmoney_ltc_account" type="checkbox" checked="checked" />');
  } else {
    $rows[] = array('LTC account', '<input name="coinmoney_ltc_account" type="checkbox" />');
  }
  if (get_option('coinmoney_xpm_account')) {
    $rows[] = array('XPM account', '<input name="coinmoney_xpm_account" type="checkbox" checked="checked" />');
  } else {
    $rows[] = array('XPM account', '<input name="coinmoney_xpm_account" type="checkbox" />');
  }
  if (get_option('coinmoney_doge_account')) {
    $rows[] = array('DOGE account', '<input name="coinmoney_doge_account" type="checkbox" checked="checked" />');
  } else {
    $rows[] = array('DOGE account', '<input name="coinmoney_doge_account" type="checkbox" />');
  }


  $output = '';
  foreach ($rows as $r) {
    $output .= '<tr> <td>' . $r[0] . '</td> <td>' . $r[1];
    if (isset($r[2]))
      $output .= '<br/><small>' . $r[2] . '</small></td> ';
    $output .= '</tr>';
  }

  return $output;
}

function submit_coinmoney()
{
  $params = array('coinmoney_apikey', 'coinmoney_merchant_id', 'coinmoney_currency', 'coinmoney_api_url',
    'coinmoney_dxx_account', 'coinmoney_btc_account', 'coinmoney_ltc_account', 'coinmoney_xpm_account', 'coinmoney_doge_account');
  foreach ($params as $p)
    if ($_POST[$p] != null) {
      update_option($p, $_POST[$p]);
    } else {
      delete_option($p);
    }

  return true;
}

function gateway_coinmoney($seperator, $sessionid)
{
  global $wpdb, $wpsc_cart;

  $purchase_log = $wpdb->get_row(
    "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS .
    "` WHERE `sessionid`= " . $sessionid . " LIMIT 1"
    , ARRAY_A);

  $usersql = "SELECT `" . WPSC_TABLE_SUBMITED_FORM_DATA . "`.value,
    `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`name`,
    `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`unique_name` FROM
    `" . WPSC_TABLE_CHECKOUT_FORMS . "` LEFT JOIN
    `" . WPSC_TABLE_SUBMITED_FORM_DATA . "` ON
    `" . WPSC_TABLE_CHECKOUT_FORMS . "`.id =
    `" . WPSC_TABLE_SUBMITED_FORM_DATA . "`.`form_id` WHERE
    `" . WPSC_TABLE_SUBMITED_FORM_DATA . "`.`log_id`=" . $purchase_log['id'];
  $userinfo = $wpdb->get_results($usersql, ARRAY_A);

  foreach ((array)$userinfo as $value)
    if (strlen($value['value']))
      $ui[$value['unique_name']] = $value['value'];
  $userinfo = $ui;

  $products = array();

  $itemTotal = 0;
  $taxTotal = $wpsc_cart->total_tax;
  $shippingTotal = number_format($wpsc_cart->base_shipping, 2);
  $shippingTotal += number_format($wpsc_cart->total_shipping, 2);


  foreach ($wpsc_cart->cart_items as $item) {
    $products[] = $item->product_name . ' x ' . $item->quantity;

    $shippingTotal += number_format($item->shipping, 2);
    $itemTotal += number_format($item->unit_price, 2) * $item->quantity;
  }

  if ($wpsc_cart->has_discounts) {
    $discountValue = number_format($wpsc_cart->cart_discount_value, 2);

    $coupon = new wpsc_coupons($wpsc_cart->cart_discount_data);

    if ($coupon->is_percentage == 2) {
      $shippingTotal = 0;
      $discountValue = 0;
    } elseif ($discountValue >= $itemTotal) {
      $discountValue = $itemTotal - 0.01;
      $shippingTotal -= 0.01;
    }
    $itemTotal -= $discountValue;
  }

  $totalAmount = number_format($itemTotal, 2) + number_format($shippingTotal, 2) + number_format($taxTotal, 2);

  if (get_option('coinmoney_currency') == 'USD') {
    $currency = 'DXX';
  } else {
    $currency = get_option('coinmoney_currency');
  }


  $allowed_currencies = array();

  if (get_option('coinmoney_dxx_account') == 'on') {
    $allowed_currencies[] = 'DXX';
  }
  if (get_option('coinmoney_btc_account') == 'on') {
    $allowed_currencies[] = 'BTC';
  }
  if (get_option('coinmoney_ltc_account') == 'on') {
    $allowed_currencies[] = 'LTC';
  }
  if (get_option('coinmoney_xpm_account') == 'on') {
    $allowed_currencies[] = 'XPM';
  }
  if (get_option('coinmoney_doge_account') == 'on') {
    $allowed_currencies[] = 'DOGE';
  }

  $options['cmd'] = 'order';
  $options['user_id'] = get_option('coinmoney_merchant_id');
  $options['amount'] = $totalAmount;
  $options['currency'] = $currency;
  $options['allowed_currencies'] = implode(', ', $allowed_currencies);
  $options['payer_pays_fee'] = 0;
  $options['quantity'] = 1;
  $options['item_name'] = implode(', ', $products);
  $options['item_number'] = $sessionid;

  if (isset($userinfo['billingfirstname'])) {
    $options['first_name'] = $userinfo['billingfirstname'];
    if (isset($userinfo['billinglastname']))
      $options['last_name'] = $userinfo['billinglastname'];
  }

  if (isset($userinfo['billingaddress'])) {
    $newline = strpos($userinfo['billingaddress'], "\n");
    if ($newline !== FALSE) {
      $options['address1'] = trim(substr($userinfo['billingaddress'], 0, $newline));
      $options['address2'] = substr($userinfo['billingaddress'], $newline + 1);
    }else{
      $options['address1'] = $userinfo['billingaddress'];
    }

  }
  if (isset($userinfo['billingstate']))
    $options['state'] = wpsc_get_state_by_id($userinfo['billingstate'], 'code');

  if (isset($userinfo['billingcountry'])) {
    $country = wpsc_country_has_state($userinfo['billingcountry']);
    $options['country'] = $country['country'];
  }
  if (isset($userinfo['billingcity']))
    $options['city'] = $userinfo['billingcity'];

  if (isset($userinfo['billingpostcode']))
    $options['zip'] = $userinfo['billingpostcode'];

  if (isset($userinfo['billingemail']))
    $options['email'] = $userinfo['billingemail'];

  $options['callback_url'] = get_option('checkout_url');

  $str = '';
  $keys = array_keys($options);
  sort($keys);
  for ($i=0; $i < count($keys); $i++) {
    $str .= $options[$keys[$i]];
  }
  $str .= get_option('coinmoney_apikey');
  $options['hash'] = md5($str);

  $result = coinmoney_send_api_call($options);

  if($result->result == 'success') {
    echo'<script> window.location="'.$result->url.'"; </script> ';
  }
}

function coinmoney_send_api_call($options) {
  $result = FALSE;

  try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, get_option('coinmoney_api_url'));
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $options);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    $json = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($json);
    if (isset($result->data)) {
      $result = json_decode($result->data);
    }
  }
  catch (Exception $e) {  }
  return $result;
}

function coinmoney_callback()
{
  global $wpdb;

  if (isset($_POST['data']) && isset($_POST['hash'])) {
    $hash = md5(stripcslashes($_POST['data']) . get_option('coinmoney_apikey'));
    if ($_POST['hash'] == $hash) {
      $data = json_decode(stripcslashes($_POST['data']));
      $sessionid = $data->item_number;

      $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS .
        "` SET `processed`= '2' WHERE `sessionid`=" . $sessionid;
      if (is_numeric($sessionid)) {
        $wpdb->query($sql);
        echo "OK";
      } else {
        header("HTTP/1.0 404 Not Found");
        die();
      }
    } else {
      header("HTTP/1.0 404 Not Found");
      die();
    }
  }
}

add_action('init', 'coinmoney_callback');