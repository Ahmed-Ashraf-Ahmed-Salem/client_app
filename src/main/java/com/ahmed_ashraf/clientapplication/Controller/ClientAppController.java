package com.ahmed_ashraf.clientapplication.Controller;

import com.ahmed_ashraf.clientapplication.Service.ClientAppService;
import com.ahmed_ashraf.clientapplication.dto.SerialNationalDTO;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/clientapp")
public class ClientAppController {

    private final ClientAppService service;

    public ClientAppController(ClientAppService service) {
        this.service = service;
    }

    @GetMapping("/serials")
    public ResponseEntity<List<SerialNationalDTO>> getSerials() {
        System.out.println("✅ Received request for /api/clientapp/serials");
        return ResponseEntity.ok(service.getAllSerialsAndNationalnos());
    }
}

