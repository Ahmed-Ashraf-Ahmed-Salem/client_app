package com.ahmed_ashraf.clientapplication.Service;

import com.ahmed_ashraf.clientapplication.Entity.*;
import com.ahmed_ashraf.clientapplication.Repository.DMClientAppDeletedRepository;
import com.ahmed_ashraf.clientapplication.Repository.DMClientAppRepository;
import com.ahmed_ashraf.clientapplication.Repository.LrOfficerRepository;
import com.ahmed_ashraf.clientapplication.dto.DeleteRequestDTO;
import com.ahmed_ashraf.clientapplication.dto.DeleteResponse;
import com.ahmed_ashraf.clientapplication.dto.DeletedRecordDTO;
import com.ahmed_ashraf.clientapplication.dto.SerialNationalDTO;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import jakarta.transaction.Transactional;
import lombok.RequiredArgsConstructor;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.util.Date;
import java.util.List;
import java.text.SimpleDateFormat;


@Service
@RequiredArgsConstructor
public class DeleteClientAppService {

//    private final String API_URL = "http://localhost/opr/delete__records.php";
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

            /*    DMClientAppDeletedId id = new DMClientAppDeletedId(dto.getSerial(), dto.getNationalno());
                entity.setId(id);
                entity.setOffBranchCode(dto.getOFF_BRANCH_CODE());
                entity.setOffCode(dto.getOFF_CODE());
                entity.setAppDate(dto.getAPPDATE());
                entity.setAppLastUpdate(dto.getLASTUPDATE()); // From PHP
                entity.setLastUpdate(new Date()); // Current system date*/

                SimpleDateFormat formatter = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                String formattedDate = formatter.format(new Date());

                DMClientAppDeletedId id = new DMClientAppDeletedId(dto.getSerial(), dto.getNationalno());
                entity.setId(id);
                entity.setOffBranchCode(dto.getOffBranchCode());
                entity.setOffCode(dto.getOffCode());
                entity.setAppDate(dto.getAppDate());
                entity.setAppLastUpdate(dto.getLastUpdate());      // ✅ From PHP
                entity.setLastUpdate(formattedDate);               // ✅ Current system date

                deletedRepo.save(entity);
            /*    try {
                    deletedRepo.save(entity);
                } catch (DataIntegrityViolationException e) {
                    System.out.println("❌ Duplicate record - already exists!");
                }*/

                // === Now update LR_OFFICER ===
                LrOfficerId officerId = new LrOfficerId(dto.getOffBranchCode(), dto.getOffCode());

                lrOfficerRepository.findById(officerId).ifPresentOrElse(officer -> {
                    // officer exists → increment
                    Integer current = officer.getNoOfAppDeleted();
                    if (current == null) current = 0;
                    officer.setNoOfAppDeleted(current + 1);
                    lrOfficerRepository.save(officer);
                }, () -> {
                    // officer does not exist → optional: create new or log warning
                    //System.out.println("Officer not found for: " + officerId);
                    throw new RuntimeException("❌ Officer not found for update: " + officerId);
                });

                // === Update DM_CLIENTAPP ===
                DMClientAppId clientAppId = new DMClientAppId(dto.getSerial(), dto.getNationalno());
                DMClientApp clientApp = dmClientAppRepository.findById(clientAppId)
                        .orElseThrow(() -> new RuntimeException("❌ Client App not found for update: " + clientAppId));

                clientApp.setOff_CODE(null);
                clientApp.setAPP_STAT("N");
                dmClientAppRepository.save(clientApp);

            } catch (Exception ex) {
                // You can log specific failure here
                throw new RuntimeException("❌ Failed to process record for Serial: " + dto.getSerial() + " And National: " + dto.getNationalno(), ex);
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
        //    headers.setContentType(MediaType.APPLICATION_FORM_URLENCODED);
            headers.set("Authorization", "Bearer " + AUTH_TOKEN);

            // log
            System.out.println("===================================");
            System.out.println(new ObjectMapper().writeValueAsString(requestDTO));
            System.out.println("===================================");


            HttpEntity<DeleteRequestDTO> entity = new HttpEntity<>(requestDTO, headers);

            System.out.println("===================================");
            System.out.println("✅ Request entity: " + new ObjectMapper().writeValueAsString(entity));
            System.out.println("===================================");

            RestTemplate restTemplate = new RestTemplate();
        //    ResponseEntity<String> response = restTemplate.postForEntity(API_URL, entity, String.class);

            ResponseEntity<String> response = restTemplate.exchange(API_URL, HttpMethod.POST, entity, String.class);
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

            // 🔍 Extract only the JSON part (from first '{' to last '}')
            int jsonStart = rawResponse.indexOf('{');
            int jsonEnd = rawResponse.lastIndexOf('}') + 1;

            if (jsonStart == -1 || jsonEnd == -1 || jsonStart >= jsonEnd) {
                return "❌ Invalid response format: " + rawResponse;
            }

            String jsonOnly = rawResponse.substring(jsonStart, jsonEnd);
            System.out.println("✅ jsonOnly Response: " + jsonOnly);

            // Parse valid JSON only
            ObjectMapper mapper = new ObjectMapper();
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

            JsonNode root = mapper.readTree(jsonOnly);

            String status = root.path("status").asText();

            if ("OK".equalsIgnoreCase(status)) {
                if (root.has("deleted_records")) {
                    DeletedRecordDTO[] recordss = mapper.treeToValue(root.get("deleted_records"), DeletedRecordDTO[].class);
                    saveDeletedRecords(List.of(recordss));
                }
            }

            return status;

         /*   String message = root.path("Message").asText();

            if ("OK".equalsIgnoreCase(status)) {
                System.out.println("✅ PHP Response");
                return "✅ Success: " + message;
            } else {
                System.out.println("❌ PHP Response");
                return "❌ Failed: " + message;
            }*/

        //    return root.path("status").asText();

        } catch (Exception e) {
            e.printStackTrace();
            return "❌ Exception occurred: " + e.getMessage();
        }
    }
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