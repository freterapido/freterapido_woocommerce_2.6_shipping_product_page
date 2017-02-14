function showHideForm() {
    if (document.getElementById("shipping-calculator-form").style.display == 'none') {
        document.getElementById("shipping-calculator-form").style.display = 'block';
    } else {
        document.getElementById("shipping-calculator-form").style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function(){
    var calc_shipping = document.getElementsByName("calc_shipping")[0];

    calc_shipping.addEventListener("click", function(){
        var variation_id = document.querySelector('.shipping_calculator input[name=calc_shipping_variation_id]');
        var current_variation_id = document.querySelector('.cart input[name=variation_id]');

        variation_id.value = current_variation_id.value;
    });
}, false);
