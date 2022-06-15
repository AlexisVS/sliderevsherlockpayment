{extends "$layout" }

{block name="content"}
    <section>
        <p>Bonjour nous somme a redirection_form.tpl</p>
        <form id="form" method="POST" action="">
            {*            <input type="hidden" name="redirectionVersion" value="<?php echo  $_SESSION['redirectionVersion']; ?>"/>*}
            {*            <input type="hidden" name="redirectionData" value="<?php echo  $_SESSION['redirectionData']; ?>"/>*}
        </form>
        <script type="text/javascript">
            document.getElementById("form").submit();
        </script>
    </section>
{/block}