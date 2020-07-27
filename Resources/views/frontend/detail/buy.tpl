{extends file="parent:frontend/detail/buy.tpl"}

{block name="frontend_detail_buy_button"}
    {$smarty.block.parent}
    {block name="frontend_detail_buy_button_includes_apple_pay_direct"}
        {include 'frontend/plugins/payment/mollie_applepay_direct.tpl' }
    {/block}
{/block}