<html dir="ltr" lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Comercia Connect</title>
    <script type="text/javascript" src="view/javascript/jquery/jquery-2.1.1.min.js"></script>
</head>
<body>
<script>
    $(document).ready(function(){
       window.opener.simple_connect_finish("<?php echo $auth_url; ?>","<?php echo $api_url; ?>","<?php echo $key; ?>");
        window.close();
    });
</script>

</body>
