{extends "$layout"}

{block name="content"}
    {debug}
    <section>
        <p>{l s='You have successfully submitted your payment form.'}</p>
        <p>{l s='Here are the params:'}</p>
        <ul>
            {foreach from=$params key=name item=value}
                <li>{$name}: {$value}</li>
            {/foreach}
        </ul>
        <hr>
        <ul>
            {foreach from=$computedResponseSeal key=name item=value}
                <li>{$name}: {$value}</li>
            {/foreach}
        </ul>
        <hr>
        <ul>
            {foreach from=$responseTable key=name item=value}
                <li>{$name}: {$value}</li>
            {/foreach}
        </ul>
        <hr>
        <ul>
            {foreach from=$requestTable key=name item=value}
                <li>{$name}: {$value}</li>
            {/foreach}
        </ul>
        <p>{l s="Now, you just need to proceed the payment and do what you need to do."}</p>
    </section>
{/block}