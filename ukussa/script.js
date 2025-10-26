document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('payment-form');
    const messageContainer = document.getElementById('message-container');
    const submitBtn = document.getElementById('submit-btn');

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        // --- Client-Side Validation ---
        const selectedPackage = form.querySelector('input[name="package"]:checked');
        const whatsappNumber = document.getElementById('whatsapp').value;
        const proofFile = document.getElementById('proof').files[0];

        let errors = [];
        if (!selectedPackage) {
            errors.push('Please select a payment package.');
        }
        if (whatsappNumber.trim() === '') {
            errors.push('Please enter your WhatsApp number.');
        } else if (!/^\d{10}$/.test(whatsappNumber.replace(/\s/g, ''))) {
             // Basic validation for a 10-digit number
            errors.push('Please enter a valid 10-digit WhatsApp number.');
        }
        if (!proofFile) {
            errors.push('Please upload your payment proof.');
        }

        if (errors.length > 0) {
            displayMessage(errors.join('<br>'), 'error');
            return;
        }

        // --- Form Submission ---
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        displayMessage('Processing your submission...', 'processing');

        const formData = new FormData();
        formData.append('package', selectedPackage.value);
        formData.append('whatsapp', whatsappNumber);
        formData.append('proof', proofFile);

        fetch('process_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayMessage(data.message, 'success');
                form.reset();
                generateReceipt(data.receipt);
            } else {
                displayMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            displayMessage('An unexpected error occurred. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Payment';
        });
    });

    function displayMessage(message, type) {
        messageContainer.innerHTML = message;
        messageContainer.className = type; // e.g., 'success', 'error'
        messageContainer.style.display = 'block';
    }

    function generateReceipt(receiptData) {
        const receiptHtml = `
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Order Receipt - ${receiptData.number}</title>
                <style>
                    body { font-family: 'Poppins', sans-serif; background: #f4f4f4; color: #333; padding: 20px; }
                    .receipt-container { max-width: 600px; margin: auto; background: white; padding: 30px; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                    h1, h2 { color: #121212; text-align: center; }
                    h1 { margin-bottom: 5px; }
                    h2 { font-size: 1rem; font-weight: normal; color: #555; margin-top: 0; margin-bottom: 30px; }
                    .details { margin-bottom: 30px; }
                    .details p { margin: 10px 0; font-size: 1.1rem; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                    .details strong { color: #000; min-width: 180px; display: inline-block; }
                    .footer-thanks { text-align: center; font-size: 1.2rem; font-weight: bold; color: #F0B90B; margin-top: 30px; }
                </style>
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
            </head>
            <body>
                <div class="receipt-container">
                    <h1>Order Receipt</h1>
                    <h2>Ukussa VIP</h2>
                    <div class="details">
                        <p><strong>Receipt Number:</strong> ${receiptData.number}</p>
                        <p><strong>WhatsApp Number:</strong> ${receiptData.whatsapp}</p>
                        <p><strong>Package Purchased:</strong> ${receiptData.package}</p>
                        <p><strong>Amount Paid:</strong> ${receiptData.amount}</p>
                        <p><strong>Payment Method:</strong> Bank Transfer / Ez Cash</p>
                        <p><strong>Date & Time:</strong> ${receiptData.dateTime}</p>
                    </div>
                    <p class="footer-thanks">Ukussa VIP – Thank you for your payment!</p>
                </div>
                <script>
                    // Optional: Trigger print dialog
                    // window.onload = function() { window.print(); }
                </script>
            </body>
            </html>
        `;

        const receiptWindow = window.open('', '_blank');
        receiptWindow.document.write(receiptHtml);
        receiptWindow.document.close();
    }
});