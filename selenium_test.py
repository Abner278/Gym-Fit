from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import time

# --- Configuration ---
BASE_URL = "http://localhost/Gym-Fit-master" # Adjust this if your local path is different
EMAIL = "member@gym.com"
PASSWORD = "member123"

def run_gym_test():
    # Setup Chrome options
    chrome_options = Options()
    # chrome_options.add_argument("--headless") # Uncomment to run without opening a browser window
    # chrome_options.add_argument("--no-sandbox")
    # chrome_options.add_argument("--disable-dev-shm-usage")
    
    # Initialize WebDriver
    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
    driver.set_window_size(1920, 1080)
    wait = WebDriverWait(driver, 10)

    try:
        print(f"1. Navigating to landing page: {BASE_URL}/index.php")
        driver.get(f"{BASE_URL}/index.php")
        print(f"Page title: {driver.title}")

        print("2. Clicking 'Login' button in navbar...")
        login_nav_btn = wait.until(EC.element_to_be_clickable((By.ID, "header-login")))
        login_nav_btn.click()

        print("3. Entering login credentials...")
        # Wait for the email field to appear
        email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
        password_field = driver.find_element(By.NAME, "password")
        login_btn = driver.find_element(By.NAME, "login")

        # Note: Your site uses 'readonly' until focus to prevent autofill. 
        # Selenium's click() will trigger the onfocus clear of readonly.
        email_field.click()
        email_field.send_keys(EMAIL)
        
        password_field.click()
        password_field.send_keys(PASSWORD)

        print("4. Submitting login form...")
        login_btn.click()

        print("5. Verifying redirection to dashboard...")
        # Wait until the URL contains dashboard_member.php
        wait.until(EC.url_contains("dashboard_member.php"))
        
        print(f"SUCCESS: Redirected to {driver.current_url}")
        
        # Optional: Verify member name is visible on dashboard
        # welcome_msg = wait.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'Welcome')]")))
        # print("Dashboard verified successfully!")

    except Exception as e:
        print(f"FAILURE: An error occurred: {e}")
        # Save a screenshot for debugging
        driver.save_screenshot("test_failure.png")
        print("Screenshot saved to test_failure.png")

    finally:
        print("Closing browser in 5 seconds...")
        time.sleep(5)
        driver.quit()

if __name__ == "__main__":
    run_gym_test()
