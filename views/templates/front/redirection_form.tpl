{extends "$layout" }

{block name="content"}
    <section>
        <form id="form" method="POST" action="{$redirectionUrl}">
            <input type="hidden" name="redirectionVersion" value="{$redirectionVersion}"/>
            <input type="hidden" name="redirectionData" value="{$redirectionData}"/>
        </form>
        <script type="text/javascript">
            document.getElementById("form").submit();
        </script>
    </section>
{/block}