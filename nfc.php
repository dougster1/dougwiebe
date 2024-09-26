<?php
$shorttimekey = md5(strtotime(date('Y-m-d H:i:s')));
$client_id_md5 = $_REQUEST['client'];
?>
<script>

    function setCookie(cname, cvalue, exhrs) {
        var d = new Date();
        d.setTime(d.getTime() + (exhrs * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + "; " + expires + ";";
    }

    function getCookie(cname) {
        var name = cname + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ')
                c = c.substring(1);
            if (c.indexOf(name) == 0) {
                var rv = c.substring(name.length, c.length);
                if (rv == null || rv == 'null' || rv == 'undefined' || typeof rv === 'undefined') {
                    rv = '';
                }
                return rv;
            }
        }
        return "";
    }




    var urlToOpen = getCookie('curl');
    var curlclient = getCookie('curlclient');

    if (curlclient != '<?php echo $client_id_md5; ?>') {
        alert('Your card/coupon does not belong to this location');
    } else if (urlToOpen == "") {
        alert('Please open your card in this same browser and try again');
    } else {
        setCookie('shc', '<?php echo $shorttimekey ?>', 1);
        console.log(urlToOpen + "&pc=<?php echo $shorttimekey; ?>");
        window.location.href = urlToOpen + "&pc=<?php echo $shorttimekey; ?>";
    }
</script>