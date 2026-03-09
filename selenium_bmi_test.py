from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import time

# --- Configuration ---
BASE_URL = "http://localhost/Gym-Fit-master"
EMAIL = "member@gym.com"
PASSWORD = "member123"

def run_bmi_test():
    # Setup Chrome options
    chrome_options = Options()
    # chrome_options.add_argument("--headless") # Uncomment to run without opening a browser window
    
    # Initialize WebDriver
    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
    wait = WebDriverWait(driver, 10)

    try:
        print(f"1. Navigating to login page: {BASE_URL}/login.php")
        driver.get(f"{BASE_URL}/login.php")
        driver.maximize_window()

        # Perform Login
        print("2. Entering credentials...")
        email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
        password_field = driver.find_element(By.NAME, "password")
        
        # Some fields might be readonly or have JS interactions, clicking helps
        email_field.click()
        email_field.send_keys(EMAIL)
        
        password_field.click()
        password_field.send_keys(PASSWORD)

        print("3. Clicking login button...")
        login_btn = driver.find_element(By.NAME, "login")
        login_btn.click()

        # Wait for dashboard to load
        print("4. Waiting for dashboard...")
        wait.until(EC.url_contains("dashboard_member.php"))
        print("Successfully logged into dashboard.")

        # Navigate to BMI Calculator section
        print("5. Clicking BMI Calculator in sidebar...")
        time.sleep(2) # Give the dashboard a moment to settle
        # Use partial link text for robustness
        bmi_nav_link = wait.until(EC.presence_of_element_located((By.PARTIAL_LINK_TEXT, "BMI Calculator")))
        # Use JavaScript click as it's more reliable for sidebar links with icons
        driver.execute_script("arguments[0].click();", bmi_nav_link)

        # Fill BMI details
        print("7. Filling BMI data (Weight: 75, Height: 180)...")
        # Ensure the inputs are ready and set values via JS for absolute certainty
        driver.execute_script("document.getElementById('bmi-weight-input').value = '75';")
        driver.execute_script("document.getElementById('bmi-height-input').value = '180';")

        print("8. Clicking Calculate BMI...")
        calc_button = driver.find_element(By.XPATH, "//div[@id='bmi-calculator']//button[contains(., 'Calculate BMI')]")
        driver.execute_script("arguments[0].click();", calc_button)

        # Handle potential alerts if inputs were somehow missing
        try:
            wait_short = WebDriverWait(driver, 2)
            alert = wait_short.until(EC.alert_is_present())
            print(f"ALERT DETECTED: {alert.text}")
            alert.accept()
        except:
            pass # No alert, good

        # Verify Result
        print("9. Verifying result...")
        # Wait for value to change from default --.-
        wait.until(lambda d: d.find_element(By.ID, "bmi-val-display").text != "--.-")
        
        result_display = driver.find_element(By.ID, "bmi-val-display")
        bmi_value = result_display.text
        bmi_category = driver.find_element(By.ID, "bmi-cat-display").text
        
        print(f"SUCCESS: Calculated BMI is {bmi_value} ({bmi_category})")
        
        # Keep browser open for a few seconds to let user see
        time.sleep(5)

    except Exception as e:
        print(f"AN ERROR OCCURRED: {e}")
        # Take a screenshot on failure
        driver.save_screenshot("bmi_test_failure.png")
        print("Failure screenshot saved as 'bmi_test_failure.png'")
    finally:
        print("Closing browser...")
        driver.quit()

if __name__ == "__main__":
    run_bmi_test()
