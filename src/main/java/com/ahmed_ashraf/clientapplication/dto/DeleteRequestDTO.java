package com.ahmed_ashraf.clientapplication.dto;

import lombok.*;

import java.util.List;

@Setter
@Getter
@Data
@AllArgsConstructor
@NoArgsConstructor
@Builder

public class DeleteRequestDTO {
    private String username;
    private String password;
    private List<SerialNationalDTO> records;
}

