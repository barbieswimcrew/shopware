{extends file="parent:frontend/detail/buy.tpl"}


{block name="frontend_detail_buy_button"}
    {$smarty.block.parent}
    {block name="frontend_detail_buy_button_includes_apple_pay_direct"}
        {include 'frontend/_includes/apple_pay_direct_button.tpl'}
    {/block}
{/block}