<html dir="ltr" lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Comercia Connect</title>
    <script type="text/javascript" src="<?php echo \Comercia\Util::filesystem()->getLatestVersion("view/javascript/jquery/jquery-",".js",true); ?>"></script>
    </head>
<body>
<script>
    $(document).ready(function(){
       window.opener.simple_connect_finish("<?php echo $auth_url; ?>","<?php echo $api_url; ?>","<?php echo $key; ?>");
        window.close();
    });
</script>

</body>
