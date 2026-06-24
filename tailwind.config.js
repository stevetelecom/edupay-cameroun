import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.js",
        "./app/Livewire/**/*.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: [
                    "-apple-system",
                    "BlinkMacSystemFont",
                    "Segoe UI",
                    ...defaultTheme.fontFamily.sans,
                ],
            },
            colors: {
                "ep-navy": "#0B2545",
                "ep-teal": "#0D9E75",
                "ep-teal2": "#0A8562",
                "ep-teal-lt": "#E0F5EE",
                "ep-teal-mid": "#9FE1CB",
                "ep-gold": "#E8A020",
                "ep-gold-lt": "#FEF3DC",
                "ep-red": "#D94040",
                "ep-red-lt": "#FBEAEA",
                "ep-blue-lt": "#E6F0FB",
                "ep-purple-lt": "#EDE9FE",
            },
            borderRadius: {
                "ep-md": "8px",
                "ep-lg": "12px",
            },
        },
    },

    plugins: [forms],
};
