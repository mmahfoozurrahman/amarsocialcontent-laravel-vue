<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AmarSocialContent OTP</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid #e9ecef;
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .header h2 {
            color: #0d6efd;
            margin: 0;
            font-size: 24px;
        }
        .otp-box {
            background: #f8f9fa;
            border: 2px dashed #0d6efd;
            border-radius: 8px;
            text-align: center;
            padding: 20px;
            margin: 24px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #0d6efd;
        }
        .notice {
            font-size: 14px;
            color: #6c757d;
            line-height: 1.6;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            text-align: center;
            font-size: 12px;
            color: #adb5bd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>AmarSocialContent</h2>
            <p style="color: #6c757d; margin-top: 6px;">সোশ্যাল মিডিয়া নলেজ অ্যাগ্রিগেটর ও সেকেন্ড ব্রেন</p>
        </div>

        <p>আসসালামু আলাইকুম / নমস্কার,</p>
        <p>আপনার AmarSocialContent অ্যাকাউন্টে লগইন বা রেজিস্ট্রেশন সম্পন্ন করতে নিচের এককালীন ওটিপি (OTP) কোডটি ব্যবহার করুন:</p>

        <div class="otp-box">
            <div class="otp-code">{{ $otp }}</div>
        </div>

        <p class="notice">
            ⚠️ <strong>নিরাপত্তা সতর্কতা:</strong> এই ওটিপি কোডটি পরবর্তী <strong>{{ $expiryMinutes }} মিনিট</strong> কার্যকর থাকবে। এই কোডটি কাউকে শেয়ার করবেন না। আপনি যদি এই কোডের জন্য অনুরোধ না করে থাকেন, তবে নির্দ্বিধায় এই ইমেইলটি উপেক্ষা করুন।
        </p>

        <div class="footer">
            &copy; {{ date('Y') }} AmarSocialContent. সর্বস্বত্ব সংরক্ষিত।
        </div>
    </div>
</body>
</html>

