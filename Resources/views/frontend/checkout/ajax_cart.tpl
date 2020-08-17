{extends file="parent:frontend/checkout/ajax_cart.tpl"}

{block name="frontend_checkout_ajax_cart_button_container_inner"}
    {$smarty.block.parent}
    {if $sBasket.content}
        <script type="text/javascript">
            // we need to (re)initialize it
            // because we are loaded within an AJAX request
            initApplePay();
        </script>
        {block name="frontend_checkout_ajax_cart_apple_pay_direct"}
            <div class="block" style="margin-top: 10px;">
                {include 'frontend/plugins/payment/mollie_applepay_direct.tpl' }
            </div>
        {/block}
    {/if}
{/block}