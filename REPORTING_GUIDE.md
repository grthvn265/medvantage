# MedVantage Advanced Reporting System

## Overview

The new advanced reporting system allows administrators to generate customizable reports from any module in MedVantage with full control over which fields to include in the export.

## Features

### 1. **Module-Based Reports**
Generate reports for the following modules:
- **Patients Report** - Patient demographics and visit history
- **Doctors Report** - Doctor information and appointment metrics
- **Appointments Report** - Appointment scheduling and status tracking
- **Billing Report** - Payment tracking and billing status
- **Visits Report** - Clinical visit details and patient care records

### 2. **Field Selection**
Customize which fields to include in your report:
- Click the **"Configure Fields"** button
- A modal will display all available fields for the selected report type
- Check/uncheck fields to customize your report
- Use **"Select All"** or **"Deselect All"** for quick adjustments

### 3. **Export Options**
Export your reports in multiple formats:
- **CSV Export** - Compatible with Excel, Google Sheets, and all spreadsheet applications
- **PDF Export** - Professional formatted reports ready for sharing and archiving

### 4. **Real-Time Metrics**
Each report displays key metrics:
- Total Records
- Report Type
- Number of Fields Selected
- Report Generation Date

## How to Use

### Step 1: Select Report Type
```
1. Open the Dashboard or Reports section
2. Use the dropdown menu to select your desired report type
3. The fields selector will automatically update
```

### Step 2: Configure Fields
```
1. Click "Configure Fields" button
2. A modal will open showing all available fields
3. Check the fields you want to include (first 5 are selected by default)
4. Click "Apply & Generate Report"
```

### Step 3: View Report
```
1. The report table will display with dynamic headers
2. Use the built-in DataTables features:
   - Search by any field
   - Sort columns by clicking headers
   - Change rows per page
   - Navigate between pages
```

### Step 4: Export Data
```
1. Click "CSV" to export as Excel-compatible CSV file
2. Click "PDF" to export as formatted PDF document
3. Exports automatically include only selected fields
4. Files are named with report type and date: {type}_report_YYYY-MM-DD.csv
```

## Available Fields by Report Type

### Patients Report
| Field | Description |
|-------|-------------|
| Patient ID | Unique patient identifier |
| Last Name | Patient surname |
| First Name | Patient given name |
| Middle Initial | Patient middle initial |
| Suffix | Name suffix (Jr., Sr., etc.) |
| Date of Birth | Patient birth date |
| Age | Calculated patient age in years |
| Sex | Patient gender |
| Address | Patient residential address |
| Contact Number | Patient phone number |
| Email | Patient email address |
| Emergency Contact | Emergency contact person name |
| Emergency Number | Emergency contact phone |
| Emergency Email | Emergency contact email |
| Registered Date | Date patient was registered |
| Total Visits | Count of clinical visits |
| Total Appointments | Count of all appointments |

### Doctors Report
| Field | Description |
|-------|-------------|
| Doctor ID | Unique doctor identifier |
| Last Name | Doctor surname |
| First Name | Doctor given name |
| Middle Initial | Doctor middle initial |
| Suffix | Name suffix |
| Date of Birth | Doctor birth date |
| Age | Calculated doctor age in years |
| Sex | Doctor gender |
| Address | Doctor residential address |
| Contact Number | Doctor phone number |
| Email | Doctor email address |
| Emergency Contact | Emergency contact person |
| Emergency Number | Emergency contact number |
| Registration Date | Date doctor was registered |
| Total Appointments | Count of all appointments |
| Completed Appointments | Count of completed appointments |

### Appointments Report
| Field | Description |
|-------|-------------|
| Appointment ID | Unique appointment identifier |
| Patient Name | Full name of patient |
| Doctor Name | Full name of assigned doctor |
| Appointment Date | Date of appointment |
| Appointment Time | Time of appointment |
| Status | Appointment status (Scheduled/Completed/Cancelled) |
| Reason | Reason for appointment |
| Patient Contact | Patient phone number |
| Patient Email | Patient email address |
| Created Date | Date appointment was created |

### Billing Report
| Field | Description |
|-------|-------------|
| Bill ID | Unique billing record identifier |
| Patient Name | Name of patient being billed |
| Description | Description of service/charges |
| Amount | Billing amount in PHP |
| Status | Payment status (Pending/Paid) |
| Due Date | Payment due date |
| Paid Date | Date payment was received |
| Created Date | Date billing record was created |
| Updated Date | Date of last modification |

### Visits Report
| Field | Description |
|-------|-------------|
| Visit ID | Unique visit record identifier |
| Patient Name | Name of patient for visit |
| Doctor Name | Doctor who conducted visit |
| Visit Date | Date of clinical visit |
| Nature of Visit | Type of visit (Consultation/Follow-up/etc.) |
| Affected Area | Body part or area of concern |
| Symptoms | Patient reported symptoms |
| Observation | Doctor observations |
| Procedure Done | Procedures performed |
| Medications | Prescribed medications |
| Instructions | Instructions given to patient |
| Remarks | Additional remarks or notes |

## Advanced Tips

### Performance Optimization
- For large datasets (>5000 records), limit field selection to essential fields
- PDF exports of large tables work best with 10-15 columns
- Use CSV for large reports for better compatibility

### Data Accuracy
- Reports show current data from the database
- Field selections are saved per report type (persists across sessions)
- All currency amounts are formatted in PHP (₱)
- Dates follow YYYY-MM-DD format unless specified otherwise

### Export Quality
**CSV Export:**
- Properly escapes special characters (quotes, commas)
- Compatible with all spreadsheet applications
- Best for data analysis and manipulation

**PDF Export:**
- Includes table headers and formatting
- Landscape orientation for better column visibility
- Suitable for printing and archiving

## Common Workflows

### Monthly Billing Summary
```
1. Select "Billing Report"
2. Configure Fields: Bill ID, Patient Name, Amount, Status, Created Date
3. Export to CSV to process payments in Excel
4. Export to PDF for archive
```

### Doctor Performance Analysis
```
1. Select "Doctors Report"
2. Configure Fields: Doctor ID, Last Name, First Name, Total Appointments, Completed Appointments
3. Use search to find specific doctors
4. Export to CSV for further analysis
```

### Patient Demographics Study
```
1. Select "Patients Report"
2. Configure Fields: Age, Sex, Address, Total Visits, Registered Date
3. Use sort features to analyze data
4. Export to CSV for statistical analysis
```

### Appointment Compliance Report
```
1. Select "Appointments Report"
2. Configure Fields: Appointment Date, Doctor Name, Patient Name, Status
3. Search by Status to filter results
4. Export to PDF for management review
```

## Troubleshooting

### No data appears in report
- Ensure at least one field is selected
- Click "Configure Fields" and then "Apply & Generate Report"
- Check that your database has records in that module

### Export button doesn't work
- Generate a report first (select fields and apply)
- Ensure browser allows downloads
- Check browser console for JavaScript errors

### Fields appear empty
- Some fields may not have data for all records (e.g., Emergency Email)
- This is normal - N/A or blank values are expected
- Use the search feature to filter records with data

### PDF export is blank
- Try with fewer columns (reduce field selection)
- Ensure table has loaded completely before exporting
- Try CSV export as alternative

## System Requirements

- Modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- Minimum screen resolution: 1024x768 (for comfortable viewing)
- PrintView capability for PDF export

## Data Privacy

- Reports contain live database data
- Ensure proper access controls are in place
- Exports should be handled securely
- Consider data sensitivity when sharing reports

## Future Enhancements

Planned features for the reporting system:
- Advanced filtering and date range selection
- Custom report templates
- Scheduled report generation and email
- Data visualization charts and analytics
- Multi-module combined reports
- Report history and versioning

---

**Last Updated:** 2026-04-05  
**Version:** 1.0
