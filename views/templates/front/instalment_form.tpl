{extends "$layout" }

{block name="content"}
    <div class="container py-3">
        <div class="row">
            <div class="d-flex flex-lg-row flex-column">

                <p class="h3 text-gray-dark pb-2">{$title}</p>

                <form action="{$redirectionUrl}" method="POST">

                    <label class="mr-2 mb-1"
                           for="instalmentPaymentNumberOfMonth">{$instalmentPaymentNumberOfMonth}</label>

                    <select class="js-instalment-form-select mr-2 mb-1" name="instalmentPaymentNumberOfMonth" id="">
                        {* Minimum i=2 *}
                        {for $i = 2; $i <= 12; $i++}
                            <option value="{$i}">{$i}</option>
                        {/for}
                    </select>
                    <input class="btn btn-primary" name="submitNewsletter"
                           type="submit" value="{$submitButtonText}">
                </form>
            </div>
        </div>
        <div class="row pt-2">
            <div style="display: flex ">
                <p class="h2 text-gray-dark  mr-1">{$labelCount}:</p>
                <p class="h2 bold js-instalment-form-amount">{$amount}</p>
                <p class="h2 bold">€</p>
            </div>
        </div>
    </div>
    <script>
        let instalmentFormSelect = document.querySelector('.js-instalment-form-select');
        let instalmentFormAmount = document.querySelector('.js-instalment-form-amount');
        const AMOUNT = instalmentFormAmount.innerText;
        instalmentFormSelect.addEventListener('change', function () {
            let selectedValue = this.value;
            let amount = AMOUNT / selectedValue;
            instalmentFormAmount.innerText = amount.toFixed(2);
        });

    </script>
{/block}
