{extends file="parent:frontend/checkout/cart.tpl"}

{block name="frontend_checkout_cart_table_actions"}
    {$smarty.block.parent}
    {block name="frontend_checkout_apple_pay_direct_top"}
        <div class="apple-pay--container">
            {include 'frontend/plugins/payment/mollie_applepay_direct.tpl'}
        </div>
    {/block}
{/block}

{block name="frontend_checkout_cart_table_actions_bottom"}
    {$smarty.block.parent}
    {block name="frontend_checkout_apple_pay_direct_bottom"}
        <div class="apple-pay--container">
            {include 'frontend/plugins/payment/mollie_applepay_direct.tpl'}
        </div>
    {/block}
{/block}