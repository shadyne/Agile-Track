import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import "vuetify/styles";
import "@mdi/font/css/materialdesignicons.css";

const vuetify = createVuetify({
  components,
  directives,
  icons: {
    defaultSet: "mdi",
  },
  theme: {
    defaultTheme: "light",
    themes: {
      light: {
        colors: {
          primary: "#020f40",
          "primary-letter": "#00164a",
          secondary: "#f3f5f6",
          line: "rgba(130, 144, 164, 0.5)",
          "second-btn": "#65a9ec",
          field: "rgba(0, 52, 246, 0.1)",
          create: "#4df142",
          cancel: "#dc3434",
          surface: "#ffffff",
          background: "#f3f5f6",
          error: "#dc3434",
          success: "#4df142",
          info: "#65a9ec",
        },
      },
    },
  },
});

export default vuetify;
