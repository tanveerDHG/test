<div class="payment-link-generator-container">
    <div class="progress-bar-container">
        <div class="progress-bar">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Basic</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Finish</div>
            </div>
        </div>
    </div>

    <div class="form-container">
        <h2>Payment Link Generator</h2>
        
        <form id="payment-link-form" class="payment-form">
            <div class="form-step active" data-step="1">
                <div class="form-group">
                    <label for="item_name">Item Name *</label>
                    <input type="text" id="item_name" name="item_name" required class="form-control">
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="item_price">Item Price *</label>
                        <input type="number" id="item_price" name="item_price" step="0.01" required class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="discount">Discount</label>
                        <input type="number" id="discount" name="discount" step="0.01" value="0" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label for="item_description">Item Description</label>
                    <textarea id="item_description" name="item_description" rows="4" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label for="item_category">Item Category *</label>
                    <input type="text" id="item_category" name="item_category" required class="form-control">
                </div>

                <div class="form-group">
                    <label for="payment_gateway">Payment Gateway *</label>
                    <select id="payment_gateway" name="payment_gateway" required class="form-control">
                        <option value="">Select Payment Gateway</option>
                        <option value="stripe">Stripe</option>
                        <option value="paypal">PayPal</option>
                        <option value="razorpay">Razorpay</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="total_cost">Total Project Cost</label>
                    <input type="number" id="total_cost" name="total_cost" step="0.01" readonly class="form-control total-cost">
                </div>

                <button type="button" class="btn btn-primary next-step">Next</button>
            </div>

            <div class="form-step" data-step="2">
                <div class="summary-container">
                    <h3>Order Summary</h3>
                    <div class="summary-content">
                        <div class="summary-item">
                            <span class="label">Item Name:</span>
                            <span class="value" id="summary-item-name"></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Category:</span>
                            <span class="value" id="summary-category"></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Price:</span>
                            <span class="value" id="summary-price"></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Discount:</span>
                            <span class="value" id="summary-discount"></span>
                        </div>
                        <div class="summary-item total">
                            <span class="label">Total Cost:</span>
                            <span class="value" id="summary-total"></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Payment Gateway:</span>
                            <span class="value" id="summary-gateway"></span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary prev-step">Previous</button>
                    <button type="submit" class="btn btn-success generate-link">Save for Link Generation</button>
                </div>
            </div>
        </form>

        <div class="generated-link-container" id="generated-link-container" style="display: none;">
            <h3>Generated Payment Link</h3>
            <div class="link-container">
                <input type="text" id="generated-link" readonly class="form-control">
                <button type="button" class="btn btn-primary copy-link">Copy Link</button>
            </div>
            <p class="link-instructions">Share this link with your customer to complete the payment.</p>
        </div>

        <div class="last-form-container">
            <span>Last Form</span>
        </div>
    </div>
</div>