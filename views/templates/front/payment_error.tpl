{extends "$layout"}

{block name="content"}
    <section class="bg-danger">
        <h1 class="text-danger">An error has been occurred.</h1>
        <p class="text-danger"></p>{$error}
    </section>
{/block}