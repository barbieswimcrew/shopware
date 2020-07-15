{extends file="parent:frontend/checkout/ajax_cart.tpl"}

{block name="frontend_checkout_ajax_cart_button_container_inner"}
    {$smarty.block.parent}
    {if $sBasket.content}
        {block name="frontend_checkout_ajax_cart_includes_apple_pay_direct"}
            {include 'frontend/_includes/apple_pay_direct_button.tpl'}
        {/block}
    {/if}
{/block}