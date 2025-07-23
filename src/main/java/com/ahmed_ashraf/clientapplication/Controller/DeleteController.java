package com.ahmed_ashraf.clientapplication.Controller;

import com.ahmed_ashraf.clientapplication.dto.ClientDeleteRequest;
import com.ahmed_ashraf.clientapplication.Service.DeleteService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/delete")
@RequiredArgsConstructor
public class DeleteController {

    private final DeleteService deleteService;

    @PostMapping
    public ResponseEntity<String> deleteClient(@RequestBody ClientDeleteRequest request) {
        String result = deleteService.deleteClient(request);
        return ResponseEntity.ok(result);
    }
}
