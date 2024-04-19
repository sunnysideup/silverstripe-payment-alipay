<h1>请注意付款详情<br  />Please note details for payment</h1>

<p><strong>货币和金额<br  />Currency and Amount:</strong> <span class="highlight">$Currency $Amount</span></p>

<p><strong>支付参考<br  />Payment Reference:</strong> <span class="highlight">$Order.ID</span></p>

<p><a href="{$Order.Link}">支付后请点击这里（请勿在支付前点击）<br >Click here once you have paid (careful not to click before you pay).</a></p>


<style>
    #PaymentLoadingImage {
        display: none;
    }
    .highlight {
        display: block;
        background-color: yellow;
        width: fit-content;
        padding: 5px;
        border-radius: 5px;
        margin: 10px auto;
    }

</style>
