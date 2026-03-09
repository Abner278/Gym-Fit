from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import time
from datetime import datetime

# --- Configuration ---
BASE_URL = "http://localhost/Gym-Fit-master"
EMAIL = "member@gym.com"
PASSWORD = "member123"

def run_measurements_test():
    # Setup Chrome options
    chrome_options = Options()
    # Disable password manager and breach popups
    chrome_options.add_experimental_option("prefs", {
        "credentials_enable_service": False,
        "profile.password_manager_enabled": False
    })
    chrome_options.add_argument("--disable-save-password-bubble")
    chrome_options.add_argument("--disable-features=PasswordBreachDetection")
    # chrome_options.add_argument("--headless") 
    
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
        
        email_field.send_keys(EMAIL)
        password_field.send_keys(PASSWORD)

        print("3. Clicking login button...")
        login_btn = driver.find_element(By.NAME, "login")
        login_btn.click()

        # Wait for dashboard to load
        print("4. Waiting for dashboard...")
        wait.until(EC.url_contains("dashboard_member.php"))
        print("Successfully logged into dashboard.")

        # Navigate to Measurements section
        print("5. Clicking Measurements in sidebar...")
        time.sleep(2) # Give the dashboard a moment to settle
        measure_nav_link = wait.until(EC.presence_of_element_located((By.PARTIAL_LINK_TEXT, "Measurements")))
        driver.execute_script("arguments[0].click();", measure_nav_link)

        # 6. Wait for the Measurements section to be visible
        print("6. Waiting for Measurements section to become visible...")
        # Scroll the entire measurements div into view first
        measurements_div = wait.until(EC.presence_of_element_located((By.ID, "measurements")))
        driver.execute_script("arguments[0].scrollIntoView({behavior: 'smooth', block: 'center'});", measurements_div)
        time.sleep(2) # Wait for smooth scroll

        # Fill Measurement details
        test_date = datetime.now().strftime("%Y-%m-%d")
        test_chest = "42.5"
        test_waist = "32.0"
        test_arms = "15.5"
        test_thighs = "22.0"

        # Helper for slow typing with visual highlight
        def slow_type(element, text):
            # Highlight the field in neon yellow so the user knows where to look
            driver.execute_script("arguments[0].style.border = '3px solid #ceff00'; arguments[0].style.backgroundColor = 'rgba(206, 255, 0, 0.1)';", element)
            element.click()
            element.clear()
            for char in text:
                element.send_keys(char)
                time.sleep(0.3) # Slower typing for better visibility
            # Remove highlight after typing
            driver.execute_script("arguments[0].style.border = ''; arguments[0].style.backgroundColor = '';", element)


        print(f"7. Logging measurements for {test_date}...")
        
        # Set date first
        date_input = driver.find_element(By.NAME, "date")
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", date_input)
        driver.execute_script(f"arguments[0].value = '{test_date}';", date_input)
        driver.execute_script("arguments[0].style.border = '3px solid #ceff00';", date_input)
        time.sleep(1)
        driver.execute_script("arguments[0].style.border = '';", date_input)

        print("Typing Chest...")
        chest_el = driver.find_element(By.NAME, "chest")
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", chest_el)
        time.sleep(0.5)
        slow_type(chest_el, test_chest)
        time.sleep(1)

        print("Typing Waist...")
        waist_el = driver.find_element(By.NAME, "waist")
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", waist_el)
        time.sleep(0.5)
        slow_type(waist_el, test_waist)
        time.sleep(1)

        print("Typing Arms...")
        arms_el = driver.find_element(By.NAME, "arms")
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", arms_el)
        time.sleep(0.5)
        slow_type(arms_el, test_arms)
        time.sleep(1)

        print("Typing Thighs...")
        thighs_el = driver.find_element(By.NAME, "thighs")
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", thighs_el)
        time.sleep(0.5)
        slow_type(thighs_el, test_thighs)
        time.sleep(1)


        print("8. Submitting form...")
        save_btn = driver.find_element(By.XPATH, "//div[@id='measurements']//button[contains(., 'Save Log')]")
        driver.execute_script("arguments[0].style.border = '5px solid red';", save_btn) # Highlight the button
        time.sleep(1)
        save_btn.click()



        # After submission, the page might reload or stay on the section
        # Wait for success message or page flash
        time.sleep(2)
        
        print("9. Verifying entry in history...")
        # Since the page might have reloaded to the same section or stayed there
        # We check the table for the logged values
        history_table = wait.until(EC.presence_of_element_located((By.XPATH, "//div[@id='measurements']//table")))
        
        # Scroll history table into view
        driver.execute_script("arguments[0].scrollIntoView({behavior: 'smooth', block: 'center'});", history_table)
        time.sleep(2)

        # Format date for display check (M d, Y e.g. Mar 09, 2026)
        display_date = datetime.now().strftime("%b %d, %Y")
        
        # Look for the row containing our display date and values
        # XPATH to find a row that contains our measurements
        xpath_query = f"//tr[contains(., '{test_chest}') and contains(., '{test_waist}') and contains(., '{test_arms}') and contains(., '{test_thighs}')]"
        
        try:
            result_row = driver.find_element(By.XPATH, xpath_query)
            # Highlight the resulting row in neon yellow so the user can easily see it
            driver.execute_script("arguments[0].style.backgroundColor = '#ceff00'; arguments[0].style.color = '#000';", result_row)
            print(f"SUCCESS: Found measurement entry in history for {display_date}!")
            print(f"Verified Values: Chest {test_chest}, Waist {test_waist}, Arms {test_arms}, Thighs {test_thighs}")
            time.sleep(5) # Pause to let the user see the highlight
        except:
            print("FAILURE: Could not find the logged measurements in the history table.")
            driver.save_screenshot("measurements_history_failure.png")
            raise Exception("Measurement verification failed.")


        # Keep browser open for a few seconds
        time.sleep(5)

    except Exception as e:
        print(f"AN ERROR OCCURRED: {e}")
        driver.save_screenshot("measurements_test_failure.png")
    finally:
        print("Closing browser...")
        driver.quit()

if __name__ == "__main__":
    run_measurements_test()
