from openpyxl import load_workbook

wb = load_workbook(filename = '/Users/sislamrafi/Documents/projects/web-devs/true-classic/database/datas/stock.xlsx')
sheet_ranges = wb['range names']
print(sheet_ranges['D18'].value)
