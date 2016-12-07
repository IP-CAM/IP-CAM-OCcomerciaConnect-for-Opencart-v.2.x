<html dir="ltr" lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Comercia Connect</title>
    <script type="text/javascript" src="view/javascript/jquery/jquery-2.1.1.min.js"></script>
</head>
<body>
<p>
    <?php echo $text_simple_connect; ?>
</p>
<p>
    http://<input type="text" id="url">/?route=simpleConnect
    <input type="button" id="finish_button" value="<?php echo $button_simple_connect_start ?>">
</p>

<script>
    $(document).ready(function(){
        $("#finish_button").click(function(){
              var domain=$("#url").val();
              window.location="http://"+domain+"/?route=simpleConnect";
        });
    });

</script>

</body>
