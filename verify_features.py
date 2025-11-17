import asyncio
from playwright.async_api import async_playwright
import os

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()

        # Get the absolute path to the HTML files
        base_path = os.path.abspath('Sistem_administrare_service')

        # --- 1. Test index.html: Cookie Banner and Back-to-Top button ---
        print("Verifying index.html...")
        index_path = f'file://{os.path.join(base_path, "index.html")}'
        await page.goto(index_path)

        # Check if cookie banner is visible
        cookie_banner = await page.query_selector('#cookie-consent-banner')
        if cookie_banner:
            print("  - Cookie banner found.")
            await page.screenshot(path='verification/01_index_cookie_banner_visible.png')

            # Click accept button and check if it hides
            await page.click('#cookie-consent-accept')
            await page.wait_for_timeout(500) # Wait for CSS transition
            is_hidden = await page.evaluate('(element) => element.getAttribute("aria-hidden") === "true"', cookie_banner)
            if is_hidden:
                print("  - Cookie banner correctly hidden after accept.")
                await page.screenshot(path='verification/02_index_cookie_banner_hidden.png')
            else:
                print("  - ERROR: Cookie banner did not hide.")
        else:
            print("  - ERROR: Cookie banner not found.")

        # Check back-to-top button icon
        back_to_top = await page.query_selector('.back-to-top')
        if back_to_top:
            icon_text = await back_to_top.inner_text()
            if icon_text == '↑':
                print("  - Back-to-top button icon is correct.")
            else:
                print(f"  - ERROR: Back-to-top button icon is incorrect. Found: '{icon_text}'")
        else:
            print("  - ERROR: Back-to-top button not found.")

        # --- 2. Test admin_clients.html: Modals ---
        print("\nVerifying admin_clients.html...")
        admin_clients_path = f'file://{os.path.join(base_path, "admin_clients.html")}'
        await page.goto(admin_clients_path)

        # Check Add Client modal
        add_client_modal = await page.query_selector('#add-client-modal')
        if add_client_modal:
            await page.click('#add-client-btn')
            await page.wait_for_timeout(300)
            is_visible = await page.evaluate('(element) => element.style.display === "block"', add_client_modal)
            if is_visible:
                print("  - Add Client modal opened successfully.")
                await page.screenshot(path='verification/03_admin_add_client_modal_visible.png')
                # Close it
                await page.click('#add-client-modal .close-modal')
                await page.wait_for_timeout(300)
            else:
                print("  - ERROR: Add Client modal did not open.")
        else:
            print("  - ERROR: Add Client modal not found.")

        # Check Delete Client modal
        delete_client_modal = await page.query_selector('#delete-client-modal')
        if delete_client_modal:
            # Click the first delete button in the table
            first_delete_button = await page.query_selector('.btn-delete')
            if first_delete_button:
                await first_delete_button.click()
                await page.wait_for_timeout(300)
                is_visible = await page.evaluate('(element) => element.style.display === "block"', delete_client_modal)
                if is_visible:
                    print("  - Delete Client modal opened successfully.")
                    await page.screenshot(path='verification/04_admin_delete_client_modal_visible.png')
                     # Close it
                    await page.click('#cancel-delete-btn')
                    await page.wait_for_timeout(300)
                else:
                    print("  - ERROR: Delete Client modal did not open.")
            else:
                print("  - No delete buttons found to test.")
        else:
            print("  - ERROR: Delete Client modal not found.")

        print("\nVerification script finished.")
        await browser.close()

if __name__ == '__main__':
    asyncio.run(main())
