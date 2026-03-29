const detailRows = document.getElementById('detailRows');
const addDetailBtn = document.getElementById('addDetailBtn');
const productForm = document.getElementById('productForm');
const checkoutForm = document.getElementById('checkoutForm');

function createDetailRow() {
  const row = document.createElement('div');
  row.className = 'detail-row';
  row.innerHTML = `
    <label>
      Detail Image
      <input type="file" name="detailImage[]" accept="image/*" required />
    </label>
    <label class="detail-caption">
      Caption
      <input type="text" name="detailCaption[]" placeholder="Highlight stitching and seams" maxlength="120" required />
    </label>
    <button type="button" class="btn btn-ghost remove-row">Remove</button>
  `;
  return row;
}

addDetailBtn.addEventListener('click', () => {
  detailRows.appendChild(createDetailRow());
});

detailRows.addEventListener('click', (event) => {
  if (!event.target.classList.contains('remove-row')) {
    return;
  }

  const rows = detailRows.querySelectorAll('.detail-row');
  if (rows.length === 1) {
    return;
  }

  event.target.closest('.detail-row').remove();
});

function setFeedback(el, message, isSuccess) {
  el.textContent = message;
  el.classList.toggle('success', isSuccess);
  el.classList.toggle('error', !isSuccess);
}

productForm.addEventListener('submit', (event) => {
  event.preventDefault();
  const feedback = document.getElementById('productFeedback');

  if (!productForm.checkValidity()) {
    productForm.reportValidity();
    setFeedback(feedback, 'Please complete all product fields and uploads.', false);
    return;
  }

  const galleryCount = document.getElementById('productImages').files.length;
  const detailCount = productForm.querySelectorAll('input[name="detailImage[]"]').length;

  setFeedback(
    feedback,
    `Product saved with ${galleryCount} main image(s) and ${detailCount} detailed section(s).`,
    true
  );

  productForm.reset();
  detailRows.innerHTML = '';
  detailRows.appendChild(createDetailRow());
});

checkoutForm.addEventListener('submit', (event) => {
  event.preventDefault();
  const feedback = document.getElementById('checkoutFeedback');

  if (!checkoutForm.checkValidity()) {
    checkoutForm.reportValidity();
    setFeedback(feedback, 'Please complete all checkout details.', false);
    return;
  }

  setFeedback(feedback, 'Order placed successfully. Confirmation sent to your email.', true);
  checkoutForm.reset();
});
