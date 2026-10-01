# Manual test checklist

Use this checklist inside a real OpenCart store as the final acceptance test for the plugin.

## 1. Install

- Upload the plugin files into the OpenCart root.
- Open the install route and confirm the plugin loads without fatal errors.
- Check that the plugin writes its job table and that the admin area can reach the route without broken assets.

## 2. Configure

- Confirm the external system is configured to call the plugin endpoints with the correct URL and credentials.
- Record the admin username and password in the store settings rather than in code.
- Verify every endpoint is reachable over HTTPS in the production store.

## 3. Product export

- Call the product export route with a valid admin credential.
- Confirm the response is valid XML and contains product ids, names, prices and category references.
- Check a product with an image and a product with no image.

## 4. Order export

- Place a test order in the store.
- Call the order export endpoint and confirm order data is returned with the expected schema.
- Validate paid, refunded and cancelled states in the XML payload.

## 5. Import flow

- Submit a sample XML payload for product import.
- Check that the job is queued and that the job status moves to completed or errored as expected.
- Review the generated log output for the job.

## 6. Error cases

- Try the export route without a valid credential.
- Send an empty or malformed XML import payload.
- Trigger a cron job with a missing or invalid job id.
- Ensure the plugin returns a clear failure message rather than a blank page.

## 7. Uninstall and rollback

- Disable or remove the plugin files from the store root.
- Confirm the job table is no longer used.
- Revert any settings value changes if required by the store operator.
