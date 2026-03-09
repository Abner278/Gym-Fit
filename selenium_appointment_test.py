from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import time
from datetime import datetime, timedelta

# --- Configuration ---
BASE_URL = "http://localhost/Gym-Fit-master"
EMAIL = "member@gym.com"
PASSWORD = "member123"

def run_appointment_test():
    chrome_options = Options()
    chrome_options.add_experimental_option("prefs", {
        "credentials_enable_service": False,
        "profile.password_manager_enabled": False
    })
    chrome_options.add_argument("--disable-save-password-bubble")
    chrome_options.add_argument("--disable-features=PasswordBreachDetection")
    
    # Capture console log
    chrome_options.set_capability('goog:loggingPrefs', {'browser': 'ALL'})
    
    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
    wait = WebDriverWait(driver, 30)

    try:
        print(f"1. Opening login...")
        driver.delete_all_cookies()
        driver.get(f"{BASE_URL}/login.php")
        driver.maximize_window()

        print("2. Logging in...")
        wait.until(EC.presence_of_element_located((By.NAME, "email"))).send_keys(EMAIL)
        driver.find_element(By.NAME, "password").send_keys(PASSWORD)
        driver.find_element(By.NAME, "login").click()

        print("3. Navigating to Trainers...")
        wait.until(EC.url_contains("dashboard_member.php"))
        time.sleep(2)
        driver.execute_script("document.getElementById('nav-trainers').click();")
        
        print("4. Selecting Samual...")
        wait.until(EC.presence_of_element_located((By.CLASS_NAME, "trainer-card")))
        driver.execute_script("""
            const cards = document.querySelectorAll('.trainer-card');
            for(let card of cards) {
                if(card.innerText.includes('Samual')) {
                    const btn = card.querySelector('button');
                    if(btn) btn.click();
                    break;
                }
            }
        """)

        print("5. Filling Modal...")
        wait.until(EC.visibility_of_element_located((By.ID, "booking-modal")))
        time.sleep(1)
        
        tomorrow = (datetime.now() + timedelta(days=1)).strftime("%Y-%m-%d")
        print(f"Selecting date: {tomorrow}")
        driver.execute_script(f"document.getElementById('flatpickr-date')._flatpickr.setDate('{tomorrow}');")
        time.sleep(1)

        print("6. Choosing 06:00 AM slot...")
        driver.execute_script("""
            const slots = document.querySelectorAll('.time-slot');
            for(let slot of slots) {
                if(slot.innerText.includes('06:00 AM')) {
                    slot.click();
                    break;
                }
            }
        """)
        time.sleep(1)

        print("7. Clicking Pay Additional button...")
        # Wait until the Check Availability resolves and button unlocks
        time.sleep(1)
        driver.execute_script("document.querySelector('#booking-modal .btn-action').click();")
        
        print("8. Clicking PAY WITH RAZORPAY...")
        # Wait for modal payment to appear
        time.sleep(2)
        rzp_pay_btn = wait.until(EC.presence_of_element_located((By.XPATH, "//button[contains(text(), 'RAZORPAY')]")))
        driver.execute_script("arguments[0].click();", rzp_pay_btn)

        print("9. Entering Razorpay Iframe...")
        time.sleep(12) # Wait for Razorpay initialization fully
        
        # Capture console outputs fully
        print("--- CONSOLE ---")
        for log in driver.get_log('browser'):
            print(log['message'])
        print("---------------")

        # Find checkout frame
        frames = driver.find_elements(By.TAG_NAME, "iframe")
        rzp_iframe = None
        for f in frames:
            src = f.get_attribute("src") or ""
            cls = f.get_attribute("class") or ""
            if "razorpay" in src or "razorpay" in cls:
                if "checkout" in src or "api.razorpay.com" in src:
                    rzp_iframe = f
                    break
        
        if rzp_iframe:
            print("Switching to Razorpay iframe!")
            driver.switch_to.frame(rzp_iframe)
        else:
            print("Could not find Razorpay Checkout iframe, trying to switch to any Razorpay iframe...")
            for f in frames:
                src = f.get_attribute("src") or ""
                if "razorpay" in src:
                    driver.switch_to.frame(f)
                    break

        time.sleep(2)
        
        # Take a screenshot to see if iframe opened
        driver.save_screenshot("screenshot_before_netbanking.png")
        print("Saved screenshot_before_netbanking.png")

        try:
            print("Checking if contact field is missing in iframe...")
            inputs = driver.find_elements(By.TAG_NAME, "input")
            for inp in inputs:
                if "contact" in (inp.get_attribute("name") or "") or inp.get_attribute("type") == "tel":
                    if inp.is_displayed():
                        print(f"Filling contact field manually in iframe (type={inp.get_attribute('type')})...")
                        # Try to focus and type
                        driver.execute_script("arguments[0].value = ''; arguments[0].focus();", inp)
                        inp.send_keys("9828394560")
                        time.sleep(1)
                        try:
                            driver.find_element(By.XPATH, "//button[contains(., 'Proceed')]").click()
                            time.sleep(3)
                        except: pass
                    break
        except Exception as e:
            print(f"Error checking contact field: {e}")

        print("10. Choosing Netbanking...")
        try:
            nb_opt = wait.until(EC.element_to_be_clickable((By.XPATH, "//*[contains(text(), 'Netbanking') or contains(text(), 'Net Banking')]")))
            driver.execute_script("arguments[0].click();", nb_opt)
            time.sleep(4)
        except Exception as e:
            print("Netbanking not found, trying again with generic wait...")
            time.sleep(3)
            nb_opt = driver.find_element(By.XPATH, "//*[contains(text(), 'Netbanking') or contains(text(), 'Net Banking')]")
            driver.execute_script("arguments[0].click();", nb_opt)

        print("11. Choosing Canara Bank...")
        try:
            canara = wait.until(EC.element_to_be_clickable((By.XPATH, "//*[contains(text(), 'Canara Bank')]")))
            driver.execute_script("arguments[0].click();", canara)
        except:
            print("Canara bank not found in top list, trying search...")
            try:
                driver.find_element(By.XPATH, "//*[contains(text(), 'Other') or contains(text(), 'All')]").click()
                time.sleep(1)
            except: pass
            try:
                search = driver.find_element(By.CSS_SELECTOR, "input[placeholder*='bank']")
                search.send_keys("Canara")
                time.sleep(2)
            except: pass
            canara = wait.until(EC.element_to_be_clickable((By.XPATH, "//*[contains(text(), 'Canara Bank')]")))
            driver.execute_script("arguments[0].click();", canara)
        
        time.sleep(3)

        print("12. Clicking Pay button in Razorpay...")
        try:
            pay_btn = WebDriverWait(driver, 5).until(EC.element_to_be_clickable((By.XPATH, "//button[contains(., 'Pay')]")))
            driver.execute_script("arguments[0].click();", pay_btn)
            time.sleep(8)
        except Exception as e:
            print("Pay button not found or already processing...")

        print("13. Handling Authorization...")
        main_handle = driver.current_window_handle
        try:
            wait.until(lambda d: len(d.window_handles) > 1)
            for h in driver.window_handles:
                if h != main_handle:
                    driver.switch_to.window(h)
                    break
            success_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(., 'Success')]")))
            success_btn.click()
            time.sleep(2)
            driver.switch_to.window(main_handle)
        except:
            print("Pop-up window not detected. Checking internal iframe button...")
            try:
                driver.switch_to.default_content()
                frames = driver.find_elements(By.TAG_NAME, "iframe")
                for f in frames:
                    if "checkout" in (f.get_attribute("src") or ""):
                        driver.switch_to.frame(f)
                        break
                wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(., 'Success')]"))).click()
                time.sleep(2)
            except: pass

        print("14. Verifying Success...")
        driver.switch_to.default_content()
        time.sleep(5)
        
        try:
            finish_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Finish Booking') or contains(text(), 'Done')]")))
            driver.execute_script("arguments[0].click();", finish_btn)
            time.sleep(3)
        except: pass

        wait.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'Success') or contains(text(), 'Booked')]")))
        print("TEST COMPLETED SUCCESSFULLY!")
        time.sleep(5)

    except Exception as e:
        print(f"AN ERROR OCCURRED: {e}")
        driver.save_screenshot("appointment_test_failure.png")
    finally:
        driver.quit()

if __name__ == "__main__":
    run_appointment_test()
