function showHideForm() {
    if (document.getElementById("shipping-calculator-form").style.display == 'none') {
        document.getElementById("shipping-calculator-form").style.display = 'block';
    } else {
        document.getElementById("shipping-calculator-form").style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function(){
    var calc_shipping = document.getElementsByName("calc_shipping")[0];

    if (!calc_shipping) {
        return;
    }

    calc_shipping.addEventListener("click", function(){
        var variation_id = document.querySelector('.shipping_calculator input[name=calc_shipping_variation_id]');
        var current_variation_id = document.querySelector('.cart input[name=variation_id]');
        var quantity = document.querySelector('.shipping_calculator input[name=calc_shipping_quantity]');
        var current_quantity = document.querySelector('.cart input[name=quantity]');

        variation_id.value = current_variation_id.value;
        quantity.value = current_quantity.value;
    });
}, false);
