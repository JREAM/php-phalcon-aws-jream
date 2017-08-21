{% if user %}
<script src="https://js.stripe.com/v2/" type="text/javascript"></script>
<script>
// @TODO Not sure why this wont load elsewhere
Stripe.setPublishableKey('{{ api.stripe.publishableKey }}');

$(function() {
    $("#form-purchase").submit(function(evt) {
        evt.preventDefault();

        var self = $(this);
        self.find('input[type=submit]').prop('disabled', true);

        Stripe.card.createToken($(this), function(status, response) {
            if (response.error) {
                self.find('.payment-errors').html('<div class="alert alert-danger">' + response.error.message + '</div>');
                self.find('input[type=submit]').prop('disabled', false);
            } else {
                var token = response.id;
                // Insert the token into the form so it gets submitted to the server
                self.append($('<input type="hidden" name="stripeToken" />').val(token));
                self.get(0).submit();
            }
        });
    });
});
</script>

<!-- Used to reference smaller screens href# -->
<div id="checkout-area"></div>

<div class="panel panel-default panel-primary checkout-purchase-paypal">
    <div class="panel-heading">
        <strong>Buy Now with Paypal</strong>
    </div>
    <div class="panel-body text-center">
        <a href="{{ url('product/dopaypal') }}/{{ product.id }}">
            <img src="{{ url('images/payments/checkout-with-paypal.jpg') }}" alt="Paypal Checkout" />
        </a>
    </div>
</div>

<div class="panel panel-default panel-primary checkout-purchase-card">
    <div class="panel-heading">
        <strong>Buy Now with Card</strong>
    </div>
    <div class="panel-body">
        <form id="form-purchase" action="{{ url('product/doStripe') }}/{{ product.id }}" method="post">
        <div class="payment-errors"></div>

            <div class="row">
                <div class="col-xs-12">
                    <div class="form-group">
                        <label>Name on Card</label>
                        <input type="text" name="name" class="form-control" placeholder="Name on Card" value="<?=formData('name')?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div class="form-group">
                        <label>Card Number</label>
                        <input data-stripe="number" class="form-control" placeholder="Card Number" value="{% if constant("\APPLICATION_ENV") != constant('\APP_DEVELOPMENT') %}4242424242424242{% endif %}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-6">
                    <div class="form-group">
                        <label>Exp. Month</label>
                        <select data-stripe="exp-month" class="form-control">
                            {% for number, name in months %}
                                <option {% if date('m') == number %}selected="selected"{% endif %} value="{{ number }}">{{ name }} - {{ number }}</option>
                            {% endfor %}
                        </select>
                    </div>
                </div>
                <div class="col-xs-6">
                    <div class="form-group">
                        <label>Exp. Year</label>
                        <select data-stripe="exp-year" class="form-control">
                            {% for year in years %}
                            <option>{{ year }}</option>
                            {% endfor %}
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-6">
                    <div class="form-group">
                        <label>Zip <span class="glyphicon glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Required for security verification."></span></label>
                        <input data-stripe="address_zip" class="form-control" value="<?=formData('zip')?>" />
                    </div>
                </div>
                <div class="col-xs-6">
                    <div class="form-group">
                        <label>CVV <span class="glyphicon glyphicon glyphicon-question-sign" data-toggle="tooltip" title="3 Digits on the back of your card. (Also known as CVC, CID, or CSC)"></span></label>
                        <input data-stripe="cvc" class="form-control" value="{% if constant("\APPLICATION_ENV") != constant('\APP_DEVELOPMENT') %}100{% endif %}" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div class="form-group">
                        <input class="btn btn-lg btn-primary btn-block popover-sm" type="submit" value="Purchase" data-toggle="popover" data-placement="top" data-original-title="Purchase for ${{ product.price }} USD" data-content="Please double check your information. If you enter incorrect information you will have to re-enter it. For security, no Credit Card data ever touches our servers.">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 text-center">
                {% include "partials/section/payments.volt" %}
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 text-center">
                    <img src="{{ url('images/payments/stripe.png') }}" alt="Powered by Stripe" />
                </div>
            </div>

            <input type="hidden" name="{{ tokenKey }}" value="{{ token }}" />

        </form>

    </div>
</div>

{% endif %}

<ul class="text-right" style="list-style-type: none;">
    <li><a href="#course-content">Course Content</a></li>
    <li><a href="#purchase-security">Purchase Security</a></li>
    <li><a href="#system-requirements">System Requirements</a></li>
    <li><a href="#discrepencies">Discrepencies</a></li>
</ul>
