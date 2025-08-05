package com.ahmed_ashraf.clientapplication.Service;

import com.ahmed_ashraf.clientapplication.Entity.*;
import com.ahmed_ashraf.clientapplication.Repository.DMClientAppDeletedRepository;
import com.ahmed_ashraf.clientapplication.Repository.DMClientAppRepository;
import com.ahmed_ashraf.clientapplication.Repository.LrOfficerRepository;
import com.ahmed_ashraf.clientapplication.dto.DeleteRequestDTO;
import com.ahmed_ashraf.clientapplication.dto.DeleteResponse;
import com.ahmed_ashraf.clientapplication.dto.DeletedRecordDTO;
import com.ahmed_ashraf.clientapplication.dto.SerialNationalDTO;
import com.fasterxml.jackson.core.JsonParser;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import jakarta.transaction.Transactional;
import lombok.RequiredArgsConstructor;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.text.SimpleDateFormat;


@Service
@RequiredArgsConstructor
public class DeleteClientAppService {

 //   private final String API_URL = "http://localhost/opr/delete__records.php";
    private final String API_URL = "https://opr.aba-apps.com:1504/webserv/delete__records.php";
    private final String AUTH_TOKEN = "jvPG6MdrLiVjOFY7aAXzeFct85ADAP";
    private final DMClientAppDeletedRepository deletedRepo;

    @Autowired
    private LrOfficerRepository lrOfficerRepository;

    @Autowired
    private DMClientAppRepository dmClientAppRepository;

    @Transactional  // ✅ Ensures atomicity of save + update
    public void saveDeletedRecords(List<DeletedRecordDTO> records) {
        for (DeletedRecordDTO dto : records) {
            try {
                DMClientAppDeleted entity = new DMClientAppDeleted();
                SimpleDateFormat formatter = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                String formattedDate = formatter.format(new Date());

                DMClientAppDeletedId id = new DMClientAppDeletedId(dto.getSerial(), dto.getNationalno());
                entity.setId(id);
                entity.setEmp_status(dto.getEmp_status());
                entity.setOffBranchCode(dto.getOffBranchCode());
                entity.setOffCode(dto.getOffCode());
                entity.setAppDate(dto.getAppDate());
                entity.setAppLastUpdate(dto.getLastUpdate());      // ✅ From PHP
                entity.setLastUpdate(formattedDate);               // ✅ Current system date

                deletedRepo.save(entity);

                // === Now update LR_OFFICER ===
                LrOfficerId officerId = new LrOfficerId(dto.getOffBranchCode(), dto.getOffCode());

                lrOfficerRepository.findById(officerId).ifPresentOrElse(officer -> {
                    // officer exists → increment
                    Integer current = officer.getNoOfAppDeleted();
                    if (current == null) current = 0;
                    officer.setNoOfAppDeleted(current + 1);
                    lrOfficerRepository.save(officer);
                }, () -> {
                    throw new RuntimeException("Officer not found for update: " + officerId);
                });

                // === Update DM_CLIENTAPP ===
                DMClientAppId clientAppId = new DMClientAppId(dto.getSerial(), dto.getNationalno());
                DMClientApp clientApp = dmClientAppRepository.findById(clientAppId)
                        .orElseThrow(() -> new RuntimeException("Client App not found for update: " + clientAppId));

                clientApp.setOff_CODE(null);
                clientApp.setAPP_STAT("N");
                dmClientAppRepository.save(clientApp);

            } catch (Exception ex) {
                // You can log specific failure here
                throw new RuntimeException("Failed to process record for Serial: " + dto.getSerial() + " And National: " + dto.getNationalno(), ex);
            }
        }
    }
    public String deleteClientApps(List<SerialNationalDTO> records) {
        try {
            // Prepare the request payload
            DeleteRequestDTO requestDTO = new DeleteRequestDTO();
            requestDTO.setUsername("System");
            requestDTO.setPassword("2023@AbaToday");
            requestDTO.setRecords(records);

            // Set headers
            HttpHeaders headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.set("Authorization", "Bearer " + AUTH_TOKEN);

            // Convert request to JSON
            ObjectMapper mapper = new ObjectMapper();
            HttpEntity<String> entity = new HttpEntity<>(mapper.writeValueAsString(requestDTO), headers);

            // Log the outgoing request
        /*    System.out.println("===================================");
            System.out.println("📤 JSON Request: " + mapper.writeValueAsString(requestDTO));
            System.out.println("===================================");
        */
            // Send request
            RestTemplate restTemplate = new RestTemplate();
            ResponseEntity<String> response = restTemplate.postForEntity(API_URL, entity, String.class);

            String rawResponse = response.getBody();

            if (rawResponse == null || rawResponse.isEmpty()) {
                System.out.println("Response body is null or empty.");
                return "FAILED";
            }

        //    System.out.println("✅ Raw PHP Response: " + rawResponse);

            // Parse JSON response correctly
            JsonNode rootNode = mapper.readTree(rawResponse);
            DeleteResponse deleteResponse = mapper.treeToValue(rootNode, DeleteResponse.class);

        //    System.out.println("✅ Message: " + deleteResponse.getMessage());

            if ("OK".equalsIgnoreCase(deleteResponse.getStatus())) {
                if (deleteResponse.getDeleted_records() != null && !deleteResponse.getDeleted_records().isEmpty()) {
                    saveDeletedRecords(deleteResponse.getDeleted_records());
                /*
                    for (DeletedRecordDTO record : deleteResponse.getDeleted_records()) {
                        System.out.println("🔸 Serial: " + record.getSerial());
                        System.out.println("🔸 NationalNo: " + record.getNationalno());
                        System.out.println("🔸 Emp_Status: " + record.getEmp_status());
                        System.out.println("🔸 Branch Code: " + record.getOffBranchCode());
                        System.out.println("🔸 Office Code: " + record.getOffCode());
                        System.out.println("🔸 App Date: " + record.getAppDate());
                        System.out.println("🔸 Last Update: " + record.getLastUpdate());
                        System.out.println("-----------------------------------");
                    }
                */
                } else {
                    System.out.println("No deleted records returned.");
                }
            } else {
                System.out.println("Delete failed: " + deleteResponse.getMessage());
            }

            return deleteResponse.getStatus();

        } catch (Exception e) {
            e.printStackTrace();
            return "Exception: " + e.getMessage();
        }
    }

/*
    public String deleteClientApps(List<SerialNationalDTO> records) {
        try {
            // Prepare the request payload
            DeleteRequestDTO requestDTO = new DeleteRequestDTO();
            requestDTO.setUsername("System");
            requestDTO.setPassword("2023@AbaToday");
            requestDTO.setRecords(records);

            // Set headers
            HttpHeaders headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.set("Authorization", "Bearer " + AUTH_TOKEN);

            // Convert request to JSON
            ObjectMapper mapper = new ObjectMapper();
            String requestJson = mapper.writeValueAsString(requestDTO);
            HttpEntity<String> entity = new HttpEntity<>(requestJson, headers);

            // Log the outgoing request
            System.out.println("===================================");
            System.out.println("📤 JSON Request: " + requestJson);
            System.out.println("===================================");

            // Send request
            RestTemplate restTemplate = new RestTemplate();
            ResponseEntity<String> response = restTemplate.postForEntity(API_URL, entity, String.class);

            String rawResponse = response.getBody();

            if (rawResponse == null || rawResponse.isEmpty()) {
                System.out.println("❌ Response body is null or empty.");
                return "FAILED";
            }

            System.out.println("✅ Raw PHP Response: " + rawResponse);

            // Extract the LAST valid JSON object
            List<JsonNode> parsedJsonObjects = new ArrayList<>();
            try (JsonParser parser = mapper.getFactory().createParser(rawResponse)) {
                while (parser.nextToken() != null) {
                    JsonNode node = mapper.readTree(parser);
                    parsedJsonObjects.add(node);
                }
            }

            if (parsedJsonObjects.isEmpty()) {
                System.out.println("❌ No valid JSON objects found in response.");
                return "FAILED";
            }

            JsonNode lastJson = parsedJsonObjects.get(parsedJsonObjects.size() - 1);

            // Map to DeleteResponse
            DeleteResponse deleteResponse = mapper.treeToValue(lastJson, DeleteResponse.class);

            // Print status and message
            System.out.println("✅ Message: " + deleteResponse.getMessage());

            if ("OK".equalsIgnoreCase(deleteResponse.getStatus())) {
                if (deleteResponse.getDeleted_records() != null && !deleteResponse.getDeleted_records().isEmpty()) {
                    saveDeletedRecords(deleteResponse.getDeleted_records());

                    for (DeletedRecordDTO record : deleteResponse.getDeleted_records()) {
                        System.out.println("🔸 Serial: " + record.getSerial());
                        System.out.println("🔸 NationalNo: " + record.getNationalno());
                        System.out.println("🔸 Branch Code: " + record.getOffBranchCode());
                        System.out.println("🔸 Office Code: " + record.getOffCode());
                        System.out.println("🔸 App Date: " + record.getAppDate());
                        System.out.println("🔸 Last Update: " + record.getLastUpdate());
                        System.out.println("-----------------------------------");
                    }
                } else {
                    System.out.println("⚠️ No deleted records returned.");
                }
            } else {
                System.out.println("❌ Delete failed: " + deleteResponse.getMessage());
            }

            return deleteResponse.getStatus();

        } catch (Exception e) {
            e.printStackTrace();
            return "❌ Exception: " + e.getMessage();
        }
    }
*/

/*
    public String deleteClientApps(List<SerialNationalDTO> records) {
        try {
            // Prepare the request payload
            DeleteRequestDTO requestDTO = new DeleteRequestDTO();
            requestDTO.setUsername("System");
            requestDTO.setPassword("2023@AbaToday");
            requestDTO.setRecords(records);

            // Set headers
            HttpHeaders headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
        //    headers.setContentType(MediaType.APPLICATION_FORM_URLENCODED);
            headers.set("Authorization", "Bearer " + AUTH_TOKEN);

            // log
            System.out.println("===================================");
            System.out.println(new ObjectMapper().writeValueAsString(requestDTO));
            System.out.println("===================================");


      //      HttpEntity<DeleteRequestDTO> entity = new HttpEntity<>(requestDTO, headers);
            HttpEntity<String> entity = new HttpEntity<>(new ObjectMapper().writeValueAsString(requestDTO), headers);

            System.out.println("===================================");
            System.out.println("✅ Request entity: " + new ObjectMapper().writeValueAsString(entity));
            System.out.println("===================================");

            RestTemplate restTemplate = new RestTemplate();
            ResponseEntity<String> response = restTemplate.postForEntity(API_URL, entity, String.class);

     //       ResponseEntity<String> response = restTemplate.exchange(API_URL, HttpMethod.POST, entity, String.class);
            String body = response.getBody(); // Should NOT be null

            System.out.println("===================================");
            System.out.println("✅ Response entity: " + new ObjectMapper().writeValueAsString(response));
            System.out.println("===================================");

            String rawResponse = response.getBody();

            if (rawResponse == null || rawResponse.isEmpty()) {
                System.out.println("❌ Response body is null or empty.");
                return "";
            }else{
                System.out.println("✅ Raw PHP Response: " + rawResponse);
            }

            System.out.println("✅ Raw PHP Response body: " + body);

/*            ObjectMapper mapper = new ObjectMapper();

// 🧠 Try to extract only the second JSON object
            List<JsonNode> parsedJsonObjects = new ArrayList<>();
            try (JsonParser parser = mapper.getFactory().createParser(rawResponse)) {
                while (parser.nextToken() != null) {
                    JsonNode node = mapper.readTree(parser);
                    parsedJsonObjects.add(node);
                }
            }

            if (parsedJsonObjects.isEmpty()) {
                return "❌ No valid JSON objects found.";
            }

            JsonNode responseJson = parsedJsonObjects.get(parsedJsonObjects.size() - 1); // last JSON block

            String status = responseJson.path("status").asText();
            if ("OK".equalsIgnoreCase(status)) {
                if (responseJson.has("deleted_records")) {
                    DeletedRecordDTO[] rec = mapper.treeToValue(responseJson.get("deleted_records"), DeletedRecordDTO[].class);
                    saveDeletedRecords(List.of(rec));
                }
            }

            DeleteResponse deleteResponse = mapper.readValue(response.getBody(), DeleteResponse.class);

            // Print the message
            System.out.println("✅ Message: " + deleteResponse.getMessage());

            // Print all deleted records
            if ("OK".equalsIgnoreCase(deleteResponse.getStatus())) {
                if (deleteResponse.getDeleted_records() != null) {
                    for (DeletedRecordDTO record : deleteResponse.getDeleted_records()) {
                        System.out.println("🔸 Serial: " + record.getSerial());
                        System.out.println("🔸 NationalNo: " + record.getNationalno());
                        System.out.println("🔸 Branch Code: " + record.getOffBranchCode());
                        System.out.println("🔸 Office Code: " + record.getOffCode());
                        System.out.println("🔸 App Date: " + record.getAppDate());
                        System.out.println("🔸 Last Update: " + record.getLastUpdate());
                        System.out.println("-----------------------------------");
                    }
                } else {
                    System.out.println("⚠️ No deleted records returned.");
                }
            } else {
                System.out.println("❌ Delete failed: " + deleteResponse.getMessage());
            }

            return status;
*/
/*
            // 🔍 Extract only the JSON part (from first '{' to last '}')
            int jsonStart = rawResponse.indexOf('{');
            int jsonEnd = rawResponse.lastIndexOf('}') + 1;

            if (jsonStart == -1 || jsonEnd == -1 || jsonStart >= jsonEnd) {
                return "❌ Invalid response format: " + rawResponse;
            }

            String jsonOnly = rawResponse.substring(jsonStart, jsonEnd);
            System.out.println("✅ jsonOnly Response: " + jsonOnly);

            // Parse valid JSON only
  //          ObjectMapper mapper = new ObjectMapper();


//---------------------------------- 3/8/2025
            DeleteResponse deleteResponse = mapper.readValue(jsonOnly, DeleteResponse.class);

            // Print the message
            System.out.println("✅ Message: " + deleteResponse.getMessage());

            // Print all deleted records
            if ("OK".equalsIgnoreCase(deleteResponse.getStatus())) {
                if (deleteResponse.getDeleted_records() != null) {
                    for (DeletedRecordDTO record : deleteResponse.getDeleted_records()) {
                        System.out.println("🔸 Serial: " + record.getSerial());
                        System.out.println("🔸 NationalNo: " + record.getNationalno());
                        System.out.println("🔸 Branch Code: " + record.getOffBranchCode());
                        System.out.println("🔸 Office Code: " + record.getOffCode());
                        System.out.println("🔸 App Date: " + record.getAppDate());
                        System.out.println("🔸 Last Update: " + record.getLastUpdate());
                        System.out.println("-----------------------------------");
                    }
                } else {
                    System.out.println("⚠️ No deleted records returned.");
                }
            } else {
                System.out.println("❌ Delete failed: " + deleteResponse.getMessage());
            }
*/
        /*    for (DeletedRecordDTO record : deleteResponse.getDeleted_records()) {
                System.out.println("🔸 Serial: " + record.getSerial());
                System.out.println("🔸 NationalNo: " + record.getNationalno());
                System.out.println("🔸 Branch Code: " + record.getOffBranchCode());
                System.out.println("🔸 Office Code: " + record.getOffCode());
                System.out.println("🔸 App Date: " + record.getAppDate());
                System.out.println("🔸 Last Update: " + record.getLastUpdate());
                System.out.println("-----------------------------------");
            }*/
//----------------------------------
/*
            JsonNode root = mapper.readTree(jsonOnly);

            String status = root.path("status").asText();

            if ("OK".equalsIgnoreCase(status)) {
                if (root.has("deleted_records")) {
                    DeletedRecordDTO[] recordss = mapper.treeToValue(root.get("deleted_records"), DeletedRecordDTO[].class);
                    saveDeletedRecords(List.of(recordss));
                }
            }

            return status;
*/
         /*   String message = root.path("Message").asText();

            if ("OK".equalsIgnoreCase(status)) {
                System.out.println("✅ PHP Response");
                return "✅ Success: " + message;
            } else {
                System.out.println("❌ PHP Response");
                return "❌ Failed: " + message;
            }*                                                                         /

        //    return root.path("status").asText();

        } catch (Exception e) {
            e.printStackTrace();
            return "❌ Exception occurred: " + e.getMessage();
        }
    }*/

}




/*package com.ahmed_ashraf.clientapplication.Service;

import com.ahmed_ashraf.clientapplication.dto.DeleteRequestDTO;
import com.ahmed_ashraf.clientapplication.dto.SerialNationalDTO;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.util.List;

@Service
public class DeleteClientAppService {

    private final String API_URL = "http://localhost/opr/delete_records.php";
    private final String AUTH_TOKEN = "jvPG6MdrLiVjOFY7aAXzeFct85ADAP";

    public String deleteClientApps(List<SerialNationalDTO> records) {
        try {
            // Build the request payload
            DeleteRequestDTO requestDTO = new DeleteRequestDTO();
            requestDTO.setUsername("System");
            requestDTO.setPassword("2023@AbaToday");
            requestDTO.setRecords(records);

            // Set headers
            HttpHeaders headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.set("Authorization", "Bearer " + AUTH_TOKEN);

            // Wrap the request
            HttpEntity<DeleteRequestDTO> entity = new HttpEntity<>(requestDTO, headers);

            // Make POST request
            RestTemplate restTemplate = new RestTemplate();
            ResponseEntity<String> response = restTemplate.postForEntity(API_URL, entity, String.class);

            System.out.println("✅ PHP Response: " + response.getBody());

            if (response.getBody() != null && response.getBody().contains("\"status\":\"OK\"")) {
                return "✅ Deleted successfully";
            } else {
                return "❌ Failed to delete: " + response.getBody();
            }

        } catch (Exception e) {
            e.printStackTrace();
            return "❌ Exception occurred: " + e.getMessage();
        }
    }
}
*/

/*package com.ahmed_ashraf.clientapplication.Service;

import com.ahmed_ashraf.clientapplication.dto.DeleteRequestDTO;
import com.ahmed_ashraf.clientapplication.dto.SerialNationalDTO;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.util.List;

@Service
public class DeleteClientAppService {

    private final String API_URL = "http://localhost/opr/delete_records.php"; // 🔁 Replace with your real URL
    private final String AUTH_TOKEN = "jvPG6MdrLiVjOFY7aAXzeFct85ADAP";

    public void deleteClientApps(List<SerialNationalDTO> records) {
        try {
            // Prepare the request object
            DeleteRequestDTO requestDTO = DeleteRequestDTO.builder()
                    .userName("System")
                    .Password("2023@AbaToday")
                    .records(records)
                    .build();

            // Convert to JSON
            ObjectMapper mapper = new ObjectMapper();
            String jsonRequest = mapper.writeValueAsString(requestDTO);

            // Set headers
            HttpHeaders headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.set("Authorization", "Bearer " + AUTH_TOKEN);

     //       HttpEntity<String> entity = new HttpEntity<>(jsonRequest, headers);
            HttpEntity<DeleteRequestDTO> entity = new HttpEntity<>(requestDTO, headers);

            // Call the API
            RestTemplate restTemplate = new RestTemplate();
            ResponseEntity<String> response = restTemplate.postForEntity(API_URL, entity, String.class);

            // 🔍 Debugging output
            System.out.println("✅ PHP Response: " + response.getBody());

            // ✅ Check actual success/failure from PHP response
            if (response.getBody().contains("\"status\":\"OK\"")) {
                return "Deleted successfully";
            } else {
                return "Failed to delete: " + response.getBody();
            }

        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}
*/