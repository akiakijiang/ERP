<?php

/**
 * greasemonkey 所用的js 页面，这个页面将根据不同的url返回不同的js，目前支持mail.google.com
 */

define('IN_ECS', true);

require('includes/init.php');
admin_priv('customer_service_manage_order');

require('function.php');

header("Content-Type:text/html");

$base_url = dirname(getScriptUrl()) . '/';

?>

function inc(src)
{
    var body = document.getElementsByTagName('body').item(0);
    script = document.createElement('script');
    script.src = src;
    script.type = 'text/javascript';
    body.appendChild(script)
}

var link = window.location.href;

// window.addEventListener('load', 
// function() {

window.setInterval(
    function() {
        link = window.location.href;
        if (link.match(/.*#([^\/]+\/)+\w{16}$/)) {
            email_order();
        }
    }
, 1000);

// }
//, true);


var latest_link = null;
var email_order_cache = {};
var latest_pre_html = '';

function email_order(){
    link = window.location.href;
    
    var canvas_frame = document.getElementById("canvas_frame");
    if (canvas_frame == null) {
        return;
    }
    
    // remove ads
    var _ads = canvas_frame.contentDocument.getElementsByClassName("oM");
    if (_ads != null && _ads.length > 0) {
        _ads[0].innerHTML = '';
    }
    
    // the content node
    var c =    canvas_frame.contentDocument.getElementsByClassName("nH u8");
    if (c == null || c.length <= 0) {
        return;   
    }
        
    if (latest_link == link) { // if the url is no change
        if (canvas_frame.contentDocument.getElementById('iframe_email_order_cache') == null) {
            c[0].innerHTML = latest_pre_html;
        }
        
        //var temp = canvas_frame.contentDocument.getElementById('iframe_email_order_cache').contentDocument.getElementsByTagName('body')[0].innerHTML;
        // canvas_frame.contentDocument.getElementById('div_email_order_cache').innerHTML = temp;
        return;
    }
    
    var headers = canvas_frame.contentDocument.getElementsByClassName("gD");
    if (headers == null || headers.length <= 0) {
        return;
    }
    
    var email = null;
    for (i = 0; i < headers.length; i++) {
        if (headers[i].textContent.charAt(headers[i].textContent.length-1) != ' ') { //marker is nbsp here, not space
            email = headers[i].getAttribute('email') ;
            break;
         }
    }
    
    if (email != null) {
        if (email == 'notice@jjshouse.com') { //notice 的提取其中的订单
            var gs = canvas_frame.contentDocument.getElementsByClassName("gs");
            if (gs != null && gs.length > 0) {
                emailcontent = gs[0].innerHTML;
                var matched = emailcontent.match(/.*Email\:[^<]*<[^<]*>(.*)<\/a>.*/);
                if (matched != null) {
                    email = matched[1];
                }
            }
        }
        
        if (email_order_cache[email] == null) {
            latest_pre_html = "<iframe height=500 width=200 margin=0 padding=0 frameborder=0 id=\"iframe_email_order_cache\" src=\"<?php print $base_url; ?>csmo.php?act=miniquery&amp;email=" + encodeURIComponent(email) +"\"></iframe>";
            email_order_cache[email] = latest_pre_html;
        } else {
            latest_pre_html = email_order_cache[email];
        }
        
        c[0].innerHTML = latest_pre_html;        
        latest_link = link;
    }
}


