<?php
define ( 'IN_ECS', true );

require_once ('../includes/init.php');
require_once ('../function.php');

admin_priv ( "jjshouse_ads_analyzer" );

$adsdb = new cls_mysql ( $ads_db_host, $ads_db_user, $ads_db_pass, $ads_db_name );

$track_id = $_REQUEST ['track_id'];
if ($track_id) {
    
    $tracks = get_tracks ( $track_id );
    if ($tracks) {
        $base = null;
        foreach ( $tracks as &$track ) {
            if (! $base) {
                $base = strtotime ( $track ['visit_time'] );
                $track ['interval'] = $track ['visit_time'];
            } else {
                $track ['interval'] = datetime_diff ( $base, $track ['visit_time'] );
            }
            
            $_behavior = $track ['url_type'];
            if (strpos ( $_behavior, 'checkout' ) !== false || strpos ( $_behavior, 'add_cart' ) !== false || strpos ( $_behavior, 'pay' ) !== false) {
                $track ['bgcolor'] = "#008040";
            }
        }
    }
    
    $smarty->assign ( 'tracks', $tracks );

}

$smarty->display ( "ads_analyzer/user_funnel.htm" );

function get_tracks($track_id) {
    global $adsdb;
    $tracks = array ();
    
    $sql = "select first_visit_time from visited_user where track_id = '{$track_id}'";
    $visit_time = strtotime ( $adsdb->getOne ( $sql ) );
    if (! $visit_time) {
        return $tracks;
    }
    $now = date ( "Ym", strtotime ( "-1 day" ) );
    do {
        $from_date = date ( "Ym", $visit_time );
        $table_name = "user_funnel_{$from_date}";
        $sql = "show tables like '{$table_name}'";
        if ($adsdb->getAll ( $sql )) {
            $sql = "select * from {$table_name} where track_id = '{$track_id}' and url_type != 'ajax' ";
            $temp_tracks = $adsdb->getAll ( $sql );
            if (is_array ( $temp_tracks )) {
                $tracks = array_merge ( $tracks, $temp_tracks );
            }
        }
        
        $visit_time = strtotime ( "+1 month", $visit_time );
    } while ( $now > $from_date );
    return $tracks;
}

function absurl($url) {
    if (stripos ( $url, 'http://' ) === false && stripos ( $url, 'https://' ) === false) {
        return "http://www.jjshouse.com/" . $url;
    }
    
    return $url;
}
