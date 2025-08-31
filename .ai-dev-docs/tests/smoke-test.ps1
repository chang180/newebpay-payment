# PowerShell smoke test for Newebpay REST API
# Usage: .\smoke-test.ps1 -SiteUrl 'http://localhost'
param(
    [Parameter(Mandatory=$true)]
    [string]$SiteUrl
)

$endpoint = "$SiteUrl/wp-json/newebpay/v1/payment-methods"
Write-Host "Querying Newebpay payment methods from: $endpoint"

try {
    $response = Invoke-RestMethod -Uri $endpoint -Method Get -UseBasicParsing -ErrorAction Stop
} catch {
    Write-Error "Failed to fetch endpoint: $_"
    exit 2
}

if ($null -eq $response) {
    Write-Error "No response received from endpoint."
    exit 3
}

if ($null -eq $response.data -or ($response.data -is [System.Array] -and $response.data.Count -eq 0) -or ($response.data -isnot [System.Array] -and $response.data.Count -eq 0)) {
    Write-Error "No payment methods returned."
    exit 4
}

# Check for frontend_id presence and cvscom_not_payed boolean
$missingFrontendId = $false
foreach ($m in $response.data) {
    if (-not $m.frontend_id) {
        Write-Warning "Payment method '$($m.id)' missing frontend_id"
        $missingFrontendId = $true
    }
}

if ($null -eq $response.cvscom_not_payed) {
    Write-Warning "Response missing 'cvscom_not_payed' field"
} else {
    Write-Host "cvscom_not_payed: $($response.cvscom_not_payed)"
}

if (-not $missingFrontendId) {
    Write-Host "All methods contain frontend_id. Methods count: $($response.data.Count)"
}

Write-Host "Smoke test completed successfully."
exit 0
