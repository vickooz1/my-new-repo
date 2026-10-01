plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

android {
    namespace = "org.mygenerationlovesgod.app"
    compileSdk = 36

    defaultConfig {
        applicationId = "org.mygenerationlovesgod.app"
        minSdk = 24
        targetSdk = 36
        versionCode = 1
        versionName = "1.0"
    }
}
