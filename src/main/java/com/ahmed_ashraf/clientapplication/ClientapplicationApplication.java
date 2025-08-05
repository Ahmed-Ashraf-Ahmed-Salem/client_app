package com.ahmed_ashraf.clientapplication;

import com.ahmed_ashraf.clientapplication.Service.ClientAppService;
import com.ahmed_ashraf.clientapplication.Service.DeleteClientAppService;
import org.springframework.boot.CommandLineRunner;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.WebApplicationType;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.boot.builder.SpringApplicationBuilder;
import org.springframework.context.ApplicationContext;
import org.springframework.context.annotation.Bean;

@SpringBootApplication
public class ClientapplicationApplication {

	public static void main(String[] args) {
	//	SpringApplication.run(ClientapplicationApplication.class, args); // Commented (Origin)
		new SpringApplicationBuilder(ClientapplicationApplication.class)
				.web(WebApplicationType.NONE) // disables embedded Tomcat
				.run(args);
	}

	@Bean
	CommandLineRunner run(DeleteClientAppService dservice, ClientAppService service) {
		return args -> {
		//	System.out.println("🚀 Running deletion process directly from main()...");
			String status = dservice.deleteClientApps(service.getAllSerialsAndNationalnos());
			if ("OK".equalsIgnoreCase(status)) {
				System.out.println("\u001B[32m" + "Records deleted successfully and archived." + "\u001B[0m");
			} else {
				System.err.println("Failed to delete some or all records.");
			}

			try {
				Thread.sleep(1000); // Sleep for 1 second
			} catch (InterruptedException e) {
				Thread.currentThread().interrupt(); // Good practice
				System.err.println("Sleep was interrupted.");
			}

			// Pause before exit so user can see output
			System.out.println("\nPress Enter to exit...");
			try {
				System.in.read();
			} catch (Exception e) {
				e.printStackTrace();
			}

		};
	}
}
