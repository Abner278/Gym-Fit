from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
import time
import os

def capture_report():
    chrome_options = Options()
    chrome_options.add_argument("--headless")
    chrome_options.add_argument("--window-size=850,900")
    
    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
    
    file_path = "file:///" + os.path.abspath("test_report_template.html").replace("\\", "/")
    print(f"Loading {file_path}")
    driver.get(file_path)
    
    # Wait to ensure fonts/layout loads
    time.sleep(2)
    
    output_img = "appointment_test_report_final_v4.png"
    driver.save_screenshot(output_img)
    print(f"Saved screenshot to {output_img}")
    
    driver.quit()

if __name__ == "__main__":
    capture_report()
