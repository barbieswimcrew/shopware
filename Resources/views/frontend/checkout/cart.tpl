{extends file="parent:frontend/checkout/cart.tpl"}

{block name="frontend_checkout_cart_table_actions"}
    {$smarty.block.parent}
    {block name="frontend_checkout_includes_apple_pay_top"}
        <div class="apple-pay--container">
            {include 'frontend/_includes/apple_pay_button.tpl'}
        </div>
    {/block}
{/block}

{block name="frontend_checkout_cart_table_actions_bottom"}
    {$smarty.block.parent}
    {block name="frontend_checkout_includes_apple_pay_bottom"}
        <div class="apple-pay--container">
            {include 'frontend/_includes/apple_pay_button.tpl'}
        </div>
    {/block}
{/block}